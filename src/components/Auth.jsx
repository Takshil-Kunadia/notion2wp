/**
 * Notion Authentication Component
 * 
 * Handles OAuth connection to Notion workspace.
 * Displays connection status and provides connect/disconnect functionality.
 * 
 * @param {Object} props - Component props
 * @param {Function} props.onStatusChange - Callback when auth status changes
 */

import { useEffect, useState } from '@wordpress/element';
import { 
	Button, 
	Card, 
	CardBody, 
	CardHeader,
	TextControl,
	Notice,
	Spinner,
	Flex,
	FlexItem,
	FlexBlock,
	ClipboardButton,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { copy } from '@wordpress/icons';

const Auth = ( { onStatusChange } ) => {
	// Get localized data from WordPress
	const apiUrl = window.notion2wpAdmin?.apiUrl || '/wp-json/notion2wp/v1/';
	const nonce = window.notion2wpAdmin?.nonce || '';
	const redirectUrl = window.notion2wpAdmin?.redirectUrl || '';

	// Component state
	const [ status, setStatus ] = useState( null );
	const [ clientId, setClientId ] = useState( '' );
	const [ clientSecret, setClientSecret ] = useState( '' );
	const [ loading, setLoading ] = useState( false );
	const [ initialLoading, setInitialLoading ] = useState( true );
	const [ message, setMessage ] = useState( '' );
	const [ error, setError ] = useState( '' );
	const [ urlCopied, setUrlCopied ] = useState( false );

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
				if ( onStatusChange ) {
					onStatusChange( data );
				}
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
	 * Handle OAuth connection initiation
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
					client_id: clientId, 
					client_secret: clientSecret, 
				} ),
			} );

			const data = await res.json();

			if ( res.ok && data.auth_url ) {
				setMessage( __( 'Redirecting to Notion for authorization...', 'notion2wp' ) );
				// Redirect to Notion OAuth page
				window.location.href = data.auth_url;
			} else {
				setError( data.message || __( 'Failed to start OAuth flow.', 'notion2wp' ) );
			}
		} catch ( err ) {
			setError( __( 'Connection error: ', 'notion2wp' ) + err.message );
		} finally {
			setLoading( false );
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
				setClientId( '' );
				setClientSecret( '' );
				
				if ( onStatusChange ) {
					onStatusChange( null );
				}
			} else {
				setError( data.message || __( 'Failed to disconnect.', 'notion2wp' ) );
			}
		} catch ( err ) {
			setError( __( 'Disconnect error: ', 'notion2wp' ) + err.message );
		} finally {
			setLoading( false );
		}
	};

	/**
	 * Handle clipboard copy success
	 */
	const handleCopySuccess = () => {
		setUrlCopied( true );
		setTimeout( () => setUrlCopied( false ), 2000 );
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
			{ message && (
				<Notice 
					status="success" 
					isDismissible 
					onRemove={ () => setMessage( '' ) }
				>
					{ message }
				</Notice>
			) }

			{ /* URL Copied Notice */ }
			{ urlCopied && (
				<Notice 
					status="info" 
					isDismissible 
					onRemove={ () => setUrlCopied( false ) }
				>
					{ __( 'URL copied to clipboard!', 'notion2wp' ) }
				</Notice>
			) }

			{ error && (
				<Notice 
					status="error" 
					isDismissible 
					onRemove={ () => setError( '' ) }
				>
					{ error }
				</Notice>
			) }

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
								{ status.workspace_name && (
									<FlexItem>
										<strong>{ __( 'Workspace:', 'notion2wp' ) }</strong>
										<div style={ { marginTop: '0.25rem', color: '#50575e' } }>
											{ status.workspace_name }
										</div>
									</FlexItem>
								) }

								{ status.workspace_icon && (
									<FlexItem>
										<strong>{ __( 'Icon:', 'notion2wp' ) }</strong>
										<div style={ { marginTop: '0.25rem', fontSize: '2rem' } }>
											{ status.workspace_icon }
										</div>
									</FlexItem>
								) }

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

								{ status.bot_id && (
									<FlexItem>
										<strong>{ __( 'Bot ID:', 'notion2wp' ) }</strong>
										<div style={ { 
											marginTop: '0.25rem', 
											fontFamily: 'monospace',
											fontSize: '0.9rem',
											color: '#50575e',
										} }>
											{ status.bot_id }
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
				/* Disconnected State - Show Connection Form */
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
								{ __( 
									'Connect your Notion workspace to start importing content. You\'ll need to create an integration in Notion first.', 
									'notion2wp', 
								) }
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
											{ __( 'Notion Integrations', 'notion2wp' ) }
										</a>
									</li>
									<li>{ __( 'Click "New integration" and fill in the details', 'notion2wp' ) }</li>
									<li>
										{ __( 'Set the Redirect URL to:', 'notion2wp' ) }
										<div style={ { 
											marginTop: '0.5rem',
											background: '#fff',
											border: '1px solid #ddd',
											borderRadius: '4px',
											padding: '0.75rem',
											fontFamily: 'monospace',
											fontSize: '0.9rem',
											wordBreak: 'break-all',
										} }>
											<Flex align="center" justify="space-between">
												<FlexBlock>
													{ redirectUrl }
												</FlexBlock>
												<FlexItem>
													<ClipboardButton
														text={ redirectUrl }
														onCopy={ handleCopySuccess }
														icon={ copy }
													/>
												</FlexItem>
											</Flex>
										</div>
									</li>
									<li>
										{ __( 'Under "Capabilities", select:', 'notion2wp' ) }
										<ul>
											<li>{ __( 'Read content', 'notion2wp' ) }</li>
											<li>{ __( 'Read comments', 'notion2wp' ) }</li>
											<li>{ __( 'Read user information (optional)', 'notion2wp' ) }</li>
										</ul>
									</li>
									<li>{ __( 'Save and copy your Client ID and Client Secret', 'notion2wp' ) }</li>
								</ol>
							</div>

							{ /* Connection Form */ }
							<form onSubmit={ handleConnect }>
								<TextControl
									label={ __( 'Notion Client ID', 'notion2wp' ) }
									value={ clientId }
									onChange={ ( value ) => setClientId( value ) }
									placeholder={ __( 'Enter your Notion Client ID', 'notion2wp' ) }
									required
									help={ __( 'Found in your Notion integration settings', 'notion2wp' ) }
								/>

								<TextControl
									label={ __( 'Notion Client Secret', 'notion2wp' ) }
									type="password"
									value={ clientSecret }
									onChange={ ( value ) => setClientSecret( value ) }
									placeholder={ __( 'Enter your Notion Client Secret', 'notion2wp' ) }
									required
									help={ __( 'Keep this secret and never share it publicly', 'notion2wp' ) }
								/>

								<div style={ { marginTop: '1.5rem' } }>
									<Button
										type="submit"
										variant="primary"
										isBusy={ loading }
										disabled={ loading || ! clientId || ! clientSecret }
									>
										{ loading 
											? __( 'Connecting...', 'notion2wp' ) 
											: __( 'Connect to Notion', 'notion2wp' )
										}
									</Button>
								</div>
							</form>
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
									href="https://developers.notion.com/docs/authorization" 
									target="_blank" 
									rel="noopener noreferrer"
								>
									{ __( 'Notion OAuth', 'notion2wp' ) }
								</a>
								{ __( ' or check our ', 'notion2wp' ) }
								<a 
									href="#" 
									target="_blank" 
									rel="noopener noreferrer"
								>
									{ __( 'documentation', 'notion2wp' ) }
								</a>
								{ __( ' for troubleshooting tips.', 'notion2wp' ) }
							</p>
						</CardBody>
					</Card>
				</>
			) }
		</div>
	);
};

export default Auth;
