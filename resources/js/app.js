import './bootstrap';
import '../css/app.css';
import App from './App.svelte';

const app = App({
  target: document.getElementById('app')
});

export default app;