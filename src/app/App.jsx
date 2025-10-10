/**
 * Notion2WP Admin Application
 *
 * Main admin interface with tabbed navigation for Settings and Import.
 * Uses WordPress TabPanel for seamless navigation between Auth and Import components.
 */

/**
 * WordPress dependencies.
 */
import { TabPanel } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { settings, download } from '@wordpress/icons';

/**
 * Internal dependencies.
 */
import Auth from '../components/Auth';
import Import from '../components/Import';

const App = () => {
	const tabs = [
		{
			name: 'import',
			title:'Import',
			icon: download,
			component: Import,
		},
		{
			name: 'settings',
			title: __( 'Settings', 'notion2wp' ),
			icon: settings,
			component: Auth,
		},
	];

	return (
		<div className="notion2wp-admin-app">
			<div className="notion2wp-admin-app__header">
				<h1>{ __( 'Notion2WP', 'notion2wp' ) }</h1>
			</div>
			<TabPanel
				className="notion2wp-admin-app__tabs"
				activeClass="is-active"
				tabs={ tabs }
			>
				{ ( tab ) => {
					const TabComponent = tab.component;
					return (
						<div className="notion2wp-admin-app__tab-content">
							<TabComponent />
						</div>
					);
				} }
			</TabPanel>
		</div>
	);
};

export default App;
