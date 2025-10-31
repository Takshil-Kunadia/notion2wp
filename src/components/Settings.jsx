/**
 * Settings Component
 *
 * Parent component that manages all settings-related functionality.
 * Includes Notion connection and role management.
 * Only accessible to administrators.
 */

import { Flex, FlexBlock } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import Connection from './Connection';
import RoleManagement from './RoleManagement';

const Settings = () => {
	return (
		<div className="sync-content-from-notion-settings">
			<Flex justify="space-between" align="flex-start" style={ { marginBottom: '1.5rem' } }>
				<FlexBlock>
					<p style={ { margin: 0, color: '#50575e', fontSize: '14px' } }>
						{ __( 'Manage your Notion connection and plugin settings.', 'sync-content-from-notion' ) }
					</p>
				</FlexBlock>
			</Flex>

			{ /* Notion Connection */ }
			<Connection />

			{ /* Role Management */ }
			<div style={ { marginTop: '1rem' } }>
				<RoleManagement />
			</div>
		</div>
	);
};

export default Settings;
