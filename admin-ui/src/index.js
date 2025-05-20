// eslint-disable-next-line no-console
console.log('[BricksLift A/B Debug] admin-ui/src/index.js parsing started.');

/**
 * WordPress dependencies
 */
import { render } from '@wordpress/element';
import { StrictMode } from 'react';

/**
 * Internal dependencies
 */
import App from './App';
import './style.scss'; // We'll create this later for basic styling

// Render the app
const BLAFTAdminRoot = document.getElementById('blft-admin-root');
if (BLAFTAdminRoot) {
	render(
		<StrictMode>
			<App />
		</StrictMode>,
		BLAFTAdminRoot
	);
} else {
	// eslint-disable-next-line no-console
	console.error('BricksLift A/B Admin Root element #blft-admin-root not found.');
}