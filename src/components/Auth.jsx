/**
 * Notion Authentication Component
 *
 * Handles Internal integration token setup for Notion workspace.
 * Displays connection status and provides connect/disconnect functionality.
 */

import { useEffect, useState } from '@wordpress/element';
import {
	Button,
	Card,
	CardBody,
	CardHeader,
	TextControl,
	Spinner,
	Flex,
	FlexItem,
	Snackbar,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';

const MESSAGE_TIMEOUT = 5000;

const Auth = () => {
	// Get localized data from WordPress
	const apiUrl = window.notion2wpAdmin?.apiUrl || '/wp-json/notion2wp/v1/';
	const nonce = window.notion2wpAdmin?.nonce || '';

	// Component state
	const [ status, setStatus ] = useState( null );
	const [ integrationToken, setIntegrationToken ] = useState( '' );
	const [ loading, setLoading ] = useState( false );
	const [ initialLoading, setInitialLoading ] = useState( true );
	const [ message, setMessage ] = useState( '' );
	const [ error, setError ] = useState( '' );

	/**
	 * Fetch current connection status from API
	 */
	const fetchStatus = async () => {
		try {
			const res = await fetch( `${ apiUrl }auth/status`, {
				headers: { 'X-WP-Nonce': nonce },
			} );

			const data = await res.json();

			if ( res.ok ) {
				setStatus( data );
			} else {
				setStatus( null );
			}
		} catch ( err ) {
			console.error( 'Failed to fetch auth status:', err );
			setStatus( null );
		} finally {
			setInitialLoading( false );
		}
	};

	// Fetch status on component mount
	useEffect( () => {
		fetchStatus();
		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [] );

	/**
	 * Handle integration connection
	 *
	 * @param {Event} e - Form submit event
	 */
	const handleConnect = async ( e ) => {
		e.preventDefault();
		setLoading( true );
		setError( '' );
		setMessage( '' );

		try {
			const res = await fetch( `${ apiUrl }auth/connect`, {
				method: 'POST',
				headers: {
					'Content-Type': 'application/json',
					'X-WP-Nonce': nonce,
				},
				body: JSON.stringify( {
					integration_token: integrationToken,
				} ),
			} );

			const data = await res.json();

			if ( res.ok && data.success ) {
				setMessage( data.message || __( 'Successfully connected to Notion!', 'notion2wp' ) );
				setIntegrationToken( '' );

				// Refresh status
				await fetchStatus();
			} else {
				setError( data.message || __( 'Failed to connect to Notion.', 'notion2wp' ) );
			}
		} catch ( err ) {
			setError( __( 'Connection error: ', 'notion2wp' ) + err.message );
		} finally {
			setLoading( false );
			clearMessages();
		}
	};

	/**
	 * Handle disconnection from Notion
	 */
	const handleDisconnect = async () => {
		if ( ! window.confirm( __( 'Are you sure you want to disconnect from Notion?', 'notion2wp' ) ) ) {
			return;
		}

		setLoading( true );
		setError( '' );
		setMessage( '' );

		try {
			const res = await fetch( `${ apiUrl }auth/disconnect`, {
				method: 'DELETE',
				headers: { 'X-WP-Nonce': nonce },
			} );

			const data = await res.json();

			if ( res.ok ) {
				setMessage( __( 'Successfully disconnected from Notion.', 'notion2wp' ) );
				setStatus( null );
				setIntegrationToken( '' );
			} else {
				setError( data.message || __( 'Failed to disconnect.', 'notion2wp' ) );
			}
		} catch ( err ) {
			setError( __( 'Disconnect error: ', 'notion2wp' ) + err.message );
		} finally {
			setLoading( false );
			clearMessages();
		}
	};

	const clearMessages = () => {
		setTimeout( () => {
			setMessage( '' );
			setError( '' );
		}, MESSAGE_TIMEOUT );
	};

	// Show loading state
	if ( initialLoading ) {
		return (
			<Card>
				<CardBody>
					<Flex align="center" justify="center" style={ { padding: '2rem' } }>
						<Spinner />
						<span style={ { marginLeft: '1rem' } }>
							{ __( 'Loading authentication status...', 'notion2wp' ) }
						</span>
					</Flex>
				</CardBody>
			</Card>
		);
	}

	return (
		<div>
			{ /* Success/Error Messages */ }
			<div style={ { marginBottom: '1rem' } }>
				{ message && (
					<Snackbar
						status="success"
						isDismissible
						onRemove={ () => setMessage( '' ) }
					>
						{ message }
					</Snackbar>
				) }

				{ error && (
					<Snackbar
						status="error"
						isDismissible
						onRemove={ () => setError( '' ) }
					>
						{ error }
					</Snackbar>
				) }
			</div>

			{ /* Main Card  */ }
			{ /* Connected State */ }
			{ status && status.connected ? (
				<Card>
					<CardHeader>
						<Flex align="center">
							<strong style={ { marginLeft: '0.5rem', color: '#46b450' } }>
								{ __( 'Connected to Notion', 'notion2wp' ) }
							</strong>
						</Flex>
					</CardHeader>
					<CardBody>
						<div style={ { marginBottom: '1.5rem' } }>
							<Flex direction="column" gap={ 3 }>

								{ status.owner && (
									<FlexItem>
										<strong>{ __( 'Owner:', 'notion2wp' ) }</strong>
										<div style={ { marginTop: '0.25rem', color: '#50575e' } }>
											{ status.owner.type === 'user'
												? status.owner.user?.name || status.owner.user?.id
												: __( 'Workspace', 'notion2wp' )
											}
										</div>
									</FlexItem>
								) }

								{ status.connection_date && (
									<FlexItem>
										<strong>{ __( 'Connected:', 'notion2wp' ) }</strong>
										<div style={ { marginTop: '0.25rem', color: '#50575e' } }>
											{ status.connection_date }
										</div>
									</FlexItem>
								) }
							</Flex>
						</div>

						<Button
							variant="secondary"
							isDestructive
							onClick={ handleDisconnect }
							isBusy={ loading }
							disabled={ loading }
						>
							{ loading
								? __( 'Disconnecting...', 'notion2wp' )
								: __( 'Disconnect from Notion', 'notion2wp' )
							}
						</Button>
					</CardBody>
				</Card>
			) : (
				/* Disconnected State - Show Internal Integration Setup */
				<>
					<Card>
						<CardHeader>
							<Flex align="center">
								<strong style={ { marginLeft: '0.5rem' } }>
									{ __( 'Connect to Notion', 'notion2wp' ) }
								</strong>
							</Flex>
						</CardHeader>
						<CardBody>
							<p style={ { marginTop: 0, color: '#50575e' } }>
								{ __( 'Connect your Notion workspace using an Internal Integration. This allows the plugin to access pages you share with it.', 'notion2wp' ) }
							</p>

							{ /* Instructions */ }
							<div style={ {
								background: '#f0f6fc',
								border: '1px solid #c3d8ec',
								borderRadius: '4px',
								padding: '1rem',
								marginBottom: '1.5rem',
							} }>
								<h4 style={ { marginTop: 0 } }>
									{ __( 'Setup Instructions:', 'notion2wp' ) }
								</h4>
								<ol style={ { marginBottom: 0, paddingLeft: '1.5rem' } }>
									<li>
										{ __( 'Go to ', 'notion2wp' ) }
										<a
											href="https://www.notion.so/my-integrations"
											target="_blank"
											rel="noopener noreferrer"
										>
											{ __( 'Notion > My Integrations', 'notion2wp' ) }
										</a>
									</li>
									<li>{ __( 'Click "+ New integration"', 'notion2wp' ) }</li>
									<li>{ __( 'Choose "Internal integration" as the type', 'notion2wp' ) }</li>
									<li>{ __( 'Give your integration a name (e.g., "WordPress Import")', 'notion2wp' ) }</li>
									<li>
										{ __( 'Under "Capabilities", enable:', 'notion2wp' ) }
										<ul style={ { marginTop: '0.5rem' } }>
											<li>{ __( '✓ Read content', 'notion2wp' ) }</li>
											<li>{ __( '✓ Read comments (optional)', 'notion2wp' ) }</li>
											<li>{ __( '✓ Read user information without email (optional)', 'notion2wp' ) }</li>
										</ul>
									</li>
									<li>{ __( 'Click "Submit" to create the integration', 'notion2wp' ) }</li>
									<li>
										{ __( 'Copy the "Internal Integration Token" (starts with "secret_")', 'notion2wp' ) }
									</li>
									<li>
										{ __( 'In Notion, share the pages/databases you want to import with your integration', 'notion2wp' ) }
									</li>
								</ol>
							</div>

							{ /* Connection Form */ }
							<TextControl
								label={ __( 'Integration Token', 'notion2wp' ) }
								type="password"
								value={ integrationToken }
								onChange={ ( value ) => setIntegrationToken( value ) }
								placeholder={ __( 'Paste your Notion Internal Integration Token here', 'notion2wp' ) }
								required
								help={ __( 'Paste your Internal Integration Token from Notion. Keep this secret!', 'notion2wp' ) }
								style={ { fontFamily: 'monospace' } }
							/>

							<div style={ { marginTop: '1.5rem' } }>
								<Button
									type="submit"
									variant="primary"
									isBusy={ loading }
									disabled={ loading || ! integrationToken }
									onClick={ handleConnect }
								>
									{ loading
										? __( 'Connecting...', 'notion2wp' )
										: __( 'Connect to Notion', 'notion2wp' )
									}
								</Button>
							</div>
						</CardBody>
					</Card>

					{ /* Additional Help Card */ }
					<Card style={ { marginTop: '1rem' } }>
						<CardBody>
							<h4 style={ { marginTop: 0 } }>
								{ __( 'Need Help?', 'notion2wp' ) }
							</h4>
							<p style={ { marginBottom: 0, color: '#50575e' } }>
								{ __( 'Learn more about ', 'notion2wp' ) }
								<a
									href="https://developers.notion.com/docs/authorization#internal-integration-auth-flow-set-up"
									target="_blank"
									rel="noopener noreferrer"
								>
									{ __( 'Notion Internal Integrations', 'notion2wp' ) }
								</a>
								{ __( ' and how to ', 'notion2wp' ) }
								<a
									href="https://www.notion.so/help/add-and-manage-connections-with-the-api#add-connections-to-pages"
									target="_blank"
									rel="noopener noreferrer"
								>
									{ __( 'share pages with your integration', 'notion2wp' ) }
								</a>
								{ __( '.', 'notion2wp' ) }
							</p>
						</CardBody>
					</Card>
				</>
			) }
		</div>
	);
};

export default Auth;
