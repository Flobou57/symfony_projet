import { startStimulusApp } from '@symfony/stimulus-bundle';
import '@symfony/ux-autocomplete';
import CartController from './controllers/cart_controller.js';
import LiveSearchController from './controllers/live_search_controller.js';

const app = startStimulusApp();
app.register('cart', CartController);
app.register('live-search', LiveSearchController);
