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

/**
 * Internal dependencies.
 */
import Settings from '../components/Settings';
import Import from '../components/Import';
import logo from '../assets/notion2wp-logo.svg';

const App = () => {
	const isAdmin = window.notion2wpAdmin?.isAdmin || false;

	const tabs = [
		{
			name: 'import',
			title: __( 'Import', 'notion2wp' ),
			component: Import,
		},
		// Only show settings tab to administrators
		...( isAdmin ? [
			{
				name: 'settings',
				title: __( 'Settings', 'notion2wp' ),
				component: Settings,
			},
		] : [] ),
	];

	return (
		<div className="notion2wp-admin-app">
			<div className="notion2wp-admin-app__header">
				<div className="notion2wp-admin-app__brand">
					<img
						src={ logo }
						alt="Notion2WP"
						className="notion2wp-admin-app__logo"
					/>
					<h1>{ __( 'Notion2WP', 'notion2wp' ) }</h1>
				</div>
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
