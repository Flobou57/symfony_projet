import { startStimulusApp } from '@symfony/stimulus-bundle';
import '@symfony/ux-autocomplete';
import CartController from './controllers/cart_controller.js';

const app = startStimulusApp();
app.register('cart', CartController);
