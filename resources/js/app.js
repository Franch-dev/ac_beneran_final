import './bootstrap';
import './animations.js';
import './navigation.js';
import { registerUiRuntime } from './ui/runtime.js';
import { installActionConfirmation } from './ui/action-confirmation.js';

registerUiRuntime();
installActionConfirmation();
