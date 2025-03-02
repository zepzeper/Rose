#!/bin/bash

# Color codes
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Error handling function
error_exit() {
    echo -e "${RED}Error: $1${NC}" >&2
    exit 1
}

# Success message function
success() {
    echo -e "${GREEN}$1${NC}"
}

# Warning message function
warning() {
    echo -e "${YELLOW}Warning: $1${NC}"
}

# Check and install Composer if not present
install_composer() {
    if ! command -v composer &> /dev/null; then
        warning "Composer not found. Installing Composer..."
        
        # Determine package manager
        if command -v apt-get &> /dev/null; then
            sudo apt-get update
            sudo apt-get install -y curl php-cli php-mbstring git unzip
        elif command -v yum &> /dev/null; then
            sudo yum install -y curl php-cli php-mbstring git unzip
        elif command -v brew &> /dev/null; then
            brew install php composer
        else
            error_exit "Unsupported package manager. Please install Composer manually."
        fi

        # Install Composer manually if not found
        if ! command -v composer &> /dev/null; then
            php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
            php composer-setup.php
            sudo mv composer.phar /usr/local/bin/composer
            php -r "unlink('composer-setup.php');"
        fi
    fi

    # Verify Composer installation
    if ! command -v composer &> /dev/null; then
        error_exit "Failed to install Composer. Please install it manually."
    fi
}

# Main script
main() {
    if [ $# -eq 0 ]; then
        error_exit "Please provide a project name"
    fi

    install_composer

    PROJECT_NAME=$1
    CURRENT_DIR=$(pwd)
    PROJECT_PATH="$CURRENT_DIR/$PROJECT_NAME"

    if [[ ! $PROJECT_NAME =~ ^[a-zA-Z0-9_-]+$ ]]; then
        error_exit "Invalid project name. Use only alphanumeric characters, underscores, and hyphens."
    fi

    if [ -d "$PROJECT_PATH" ]; then
        error_exit "Directory $PROJECT_NAME already exists"
    fi

    mkdir -p "$PROJECT_PATH"
    cd "$PROJECT_PATH"

    composer init --no-interaction --name="$PROJECT_NAME/project" --require="php:*" --stability=dev

    # Add repository explicitly in composer.json instead of dynamically
    echo '{
        "repositories": [
            {
                "type": "vcs",
                "url": "https://github.com/zepzeper/Rose.git"
            }
        ],
        "minimum-stability": "dev",
        "prefer-stable": true
    }' > composer.json

    # Require the correct package version
    composer require rose/framework:dev-master --prefer-source --verbose

    if [ $? -ne 0 ]; then
        error_exit "Failed to create project with Composer"
    fi

    git init > /dev/null
    git add .
    git commit -m "Initial project setup" > /dev/null

    # Fix incorrect PHP execution
    wget -O start_kit.php https://raw.githubusercontent.com/zepzeper/Rose/refs/heads/master/setup/start_kit.php
    php start_kit.php
    rm start_kit.php

    success "Project $PROJECT_NAME created successfully!"
    echo ""
    success "Next steps:"
    echo "1. cd $PROJECT_NAME"
    echo "2. npm install"
    echo "3. npm run dev"
}

main "$@"
