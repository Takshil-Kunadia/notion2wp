import { createRoot } from '@wordpress/element';
import App from './app/App';

/**
 * Import Style.
 */
import './style.scss';

// Admin app root - renders tabbed interface
const adminRoot = document.getElementById( 'notion2wp-admin-root' );
if ( adminRoot ) {
	createRoot( adminRoot ).render( <App /> );
}
