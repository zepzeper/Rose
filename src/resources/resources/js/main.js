import 'htmx.org';
import '../css/main.css';

console.log('HTMX is ready!');

// Test HTMX is working
document.addEventListener('htmx:configRequest', (event) => {
  console.log('HTMX request configured:', event);
});
