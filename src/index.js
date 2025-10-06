import { createRoot, useState } from '@wordpress/element';
import NotionAuth from './components/NotionAuth';

const App = () => {
	const [ authStatus, setAuthStatus ] = useState( null ); // eslint-disable-line no-unused-vars

	return (
		<div>
			<NotionAuth onStatusChange={setAuthStatus} />
		</div>
	);
};

const root = document.getElementById( 'notion2wp-admin-root' );
if ( root ) {
	createRoot( root ).render( <App /> );
}
