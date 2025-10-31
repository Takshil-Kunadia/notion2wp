/**
 * Sync Content From Notion Admin Application
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
import Settings from '../components/Settings';
import Import from '../components/Import';

const App = () => {
	const isAdmin = window.syncContentFromNotionAdmin?.isAdmin || false;

	const tabs = [
		{
			name: 'import',
			title: __( 'Import', 'sync-content-from-notion' ),
			icon: download,
			component: Import,
		},
		// Only show settings tab to administrators
		...( isAdmin ? [
			{
				name: 'settings',
				title: __( 'Settings', 'sync-content-from-notion' ),
				icon: settings,
				component: Settings,
			},
		] : [] ),
	];

	return (
		<div className="sync-content-from-notion-admin-app">
			<div className="sync-content-from-notion-admin-app__header">
				<h1>{ __( 'Sync Content From Notion', 'sync-content-from-notion' ) }</h1>
			</div>
			<TabPanel
				className="sync-content-from-notion-admin-app__tabs"
				activeClass="is-active"
				tabs={ tabs }
			>
				{ ( tab ) => {
					const TabComponent = tab.component;
					return (
						<div className="sync-content-from-notion-admin-app__tab-content">
							<TabComponent />
						</div>
					);
				} }
			</TabPanel>
		</div>
	);
};

export default App;
