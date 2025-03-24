import './bootstrap';
import '../css/app.css';
import App from './App.svelte';

console.log(typeof App); // Debería mostrar "function"

const app = new App({  // Quita el ".default" aquí
  target: document.getElementById('app')
});

export default app;