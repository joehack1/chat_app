// resources/js/app.js
import './bootstrap';
import { createApp } from 'vue';
import ChatContainer from './Components/Chat/ChatContainer.vue';

const app = createApp({});

// Register components
app.component('ChatContainer', ChatContainer);

// Mount the app
app.mount('#app');