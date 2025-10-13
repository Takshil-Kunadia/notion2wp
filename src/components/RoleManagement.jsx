/**
 * Role Management Component
 *
 * Allows administrators to manage which user roles can access Notion2WP.
 * Fetches available roles and current permissions from REST API.
 */

import { useEffect, useState } from '@wordpress/element';
import {
	Card,
	CardBody,
	CardHeader,
	CheckboxControl,
	Spinner,
	Notice,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';

const RoleManagement = () => {
	// Get localized data from WordPress
	const apiUrl = window.notion2wpAdmin?.apiUrl || '/wp-json/notion2wp/v1/';
	const nonce = window.notion2wpAdmin?.nonce || '';

	// Component state
	const [ availableRoles, setAvailableRoles ] = useState( [] );
	const [ allowedRoles, setAllowedRoles ] = useState( [] );
	const [ loading, setLoading ] = useState( true );
	const [ saving, setSaving ] = useState( false );
	const [ message, setMessage ] = useState( null );
	const [ error, setError ] = useState( null );

	/**
	 * Fetch role capabilities from API
	 */
	const fetchRoleCapabilities = async () => {
		setLoading( true );
		setError( null );

		try {
			const res = await fetch( `${ apiUrl }capabilities/roles`, {
				headers: { 'X-WP-Nonce': nonce },
			} );

			const data = await res.json();

			if ( res.ok ) {
				setAvailableRoles( data.available_roles || [] );
				setAllowedRoles( data.allowed_roles || [] );
			} else {
				setError( data.message || __( 'Failed to fetch role capabilities.', 'notion2wp' ) );
			}
		} catch ( err ) {
			setError( __( 'Error fetching role capabilities: ', 'notion2wp' ) + err.message );
		} finally {
			setLoading( false );
		}
	};

	// Fetch role capabilities on component mount
	useEffect( () => {
		fetchRoleCapabilities();
		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [] );

	/**
	 * Handle role toggle
	 *
	 * @param {string} roleSlug - The role slug to toggle
	 */
	const handleRoleToggle = async ( roleSlug ) => {
		// Administrator is always enabled and can't be toggled
		if ( roleSlug === 'administrator' ) {
			return;
		}

		setSaving( true );
		setError( null );
		setMessage( null );

		// Optimistically update UI
		const updatedRoles = allowedRoles.includes( roleSlug )
			? allowedRoles.filter( ( role ) => role !== roleSlug )
			: [ ...allowedRoles, roleSlug ];

		const previousRoles = [ ...allowedRoles ];
		setAllowedRoles( updatedRoles );

		try {
			const res = await fetch( `${ apiUrl }capabilities/roles`, {
				method: 'POST',
				headers: {
					'Content-Type': 'application/json',
					'X-WP-Nonce': nonce,
				},
				body: JSON.stringify( {
					roles: updatedRoles,
				} ),
			} );

			const data = await res.json();

			if ( res.ok && data.success ) {
				setMessage( {
					text: data.message || __( 'Roles updated successfully!', 'notion2wp' ),
					type: 'success',
				} );
				// Update with server response to ensure consistency
				if ( data.allowed_roles ) {
					setAllowedRoles( data.allowed_roles );
				}
			} else {
				// Revert on error
				setAllowedRoles( previousRoles );
				setError( data.message || __( 'Failed to update roles.', 'notion2wp' ) );
			}
		} catch ( err ) {
			// Revert on error
			setAllowedRoles( previousRoles );
			setError( __( 'Error updating roles: ', 'notion2wp' ) + err.message );
		} finally {
			setSaving( false );
			// Clear messages after 5 seconds
			setTimeout( () => {
				setMessage( null );
				setError( null );
			}, 5000 );
		}
	};

	// Show loading state
	if ( loading ) {
		return (
			<Card>
				<CardBody>
					<div style={ { display: 'flex', alignItems: 'center', gap: '0.5rem' } }>
						<Spinner />
						<span>{ __( 'Loading role permissions...', 'notion2wp' ) }</span>
					</div>
				</CardBody>
			</Card>
		);
	}

	return (
		<Card>
			<CardHeader>
				<strong>{ __( 'Role Permissions', 'notion2wp' ) }</strong>
			</CardHeader>
			<CardBody>
				{ /* Success/Error Messages */ }
				{ message && (
					<Notice
						status={ message.type }
						isDismissible
						onRemove={ () => setMessage( null ) }
					>
						{ message.text }
					</Notice>
				) }

				{ error && (
					<Notice
						status="error"
						isDismissible
						onRemove={ () => setError( null ) }
					>
						{ error }
					</Notice>
				) }

				<p style={ { marginTop: message || error ? '1rem' : 0, color: '#50575e' } }>
					{ __( 'Select which user roles can access and import pages.', 'notion2wp' ) }
				</p>

				{ /* Role Checkboxes */ }
				<div style={ { marginTop: '1rem' } }>
					{ availableRoles.length > 0 ? (
						availableRoles.map( ( role ) => (
							<CheckboxControl
								key={ role.name }
								label={ role.display_name }
								checked={ allowedRoles.includes( role.name ) }
								onChange={ () => handleRoleToggle( role.name ) }
								disabled={ saving || role.name === 'administrator' }
								help={ role.name === 'administrator' ? __( 'Administrators always have access', 'notion2wp' ) : '' }
							/>
						) )
					) : (
						<p style={ { color: '#757575', fontStyle: 'italic' } }>
							{ __( 'No roles available.', 'notion2wp' ) }
						</p>
					) }
				</div>

				{ /* Saving Indicator */ }
				{ saving && (
					<div style={ { marginTop: '1rem', display: 'flex', alignItems: 'center', gap: '0.5rem' } }>
						<Spinner />
						<span style={ { color: '#757575' } }>
							{ __( 'Updating roles...', 'notion2wp' ) }
						</span>
					</div>
				) }
			</CardBody>
		</Card>
	);
};

export default RoleManagement;
