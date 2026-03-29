/**
 * Settings Component
 *
 * Parent component that manages all settings-related functionality.
 * Includes Notion connection and role management.
 * Only accessible to administrators.
 */

import { __ } from '@wordpress/i18n';
import Connection from './Connection';
import RoleManagement from './RoleManagement';

const Settings = () => {
	return (
		<div className="notion2wp-settings">
			<p className="notion2wp-settings__description">
				{ __( 'Manage your Notion connection and plugin settings.', 'notion2wp' ) }
			</p>
			<Connection />
			<div className="notion2wp-settings__section">
				<RoleManagement />
			</div>
		</div>
	);
};

export default Settings;
