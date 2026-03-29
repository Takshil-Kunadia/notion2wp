/**
 * Notion Connection Component
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
	Snackbar,
	__experimentalConfirmDialog as ConfirmDialog,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { help } from '@wordpress/icons';
import { useSelect, useDispatch } from '@wordpress/data';
import { store as coreStore } from '@wordpress/core-data';

/**
 * Internal dependencies
 */
import SetupGuide from './SetupGuide';

const MESSAGE_TIMEOUT = 5000;

const Connection = () => {
	// Get localized data from WordPress
	const apiUrl = window.notion2wpAdmin?.apiUrl || '/wp-json/notion2wp/v1/';
	const nonce = window.notion2wpAdmin?.nonce || '';

	// Get setup guide status from WordPress options via core-data
	const setupGuideShown = useSelect( ( select ) => {
		const option = select( coreStore ).getEntityRecord( 'root', 'site' );
		return option?.notion2wp_setup_guide_shown;
	}, [] );

	const { saveEntityRecord } = useDispatch( coreStore );

	// Component state
	const [ status, setStatus ] = useState( null );
	const [ integrationToken, setIntegrationToken ] = useState( '' );
	const [ loading, setLoading ] = useState( false );
	const [ initialLoading, setInitialLoading ] = useState( true );
	const [ message, setMessage ] = useState( '' );
	const [ error, setError ] = useState( '' );
	const [ showDisconnectConfirm, setShowDisconnectConfirm ] = useState( false );
	const [ showSetupGuide, setShowSetupGuide ] = useState( false );

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

	/**
	 * Handle setup guide finish
	 */
	const handleGuideFinish = async () => {
		setShowSetupGuide( false );

		// Save the option using core-data
		try {
			await saveEntityRecord( 'root', 'site', {
				notion2wp_setup_guide_shown: true,
			} );
		} catch ( err ) {
			setError( 'Failed to mark setup guide as shown:' + err );
		}
	};

	// Fetch status on component mount
	useEffect( () => {
		fetchStatus();
		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [] );

	// Show setup guide automatically if it hasn't been shown before
	useEffect( () => {
		if ( false === setupGuideShown ) {
			setShowSetupGuide( true );
		}
	}, [ setupGuideShown ] );

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
		setShowDisconnectConfirm( false );
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
					<div className="notion2wp-loading">
						<Spinner />
						<span className="notion2wp-loading__text">
							{ __( 'Loading authentication status...', 'notion2wp' ) }
						</span>
					</div>
				</CardBody>
			</Card>
		);
	}

	return (
		<div className="notion2wp-connection">
			{ /* Setup Guide Modal */ }
			{ showSetupGuide && (
				<SetupGuide onFinish={ handleGuideFinish } />
			) }

			{ /* Disconnect Confirmation Dialog */ }
			<ConfirmDialog
				isOpen={ showDisconnectConfirm }
				onConfirm={ handleDisconnect }
				onCancel={ () => setShowDisconnectConfirm( false ) }
				confirmButtonText={ __( 'Disconnect', 'notion2wp' ) }
				cancelButtonText={ __( 'Cancel', 'notion2wp' ) }
			>
				{ __( 'Are you sure you want to disconnect from Notion? You will need to reconnect to import more content.', 'notion2wp' ) }
			</ConfirmDialog>

			{ /* Success/Error Messages */ }
			{ ( message || error ) && (
				<div className="notion2wp-connection__messages">
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
			) }

			{ /* Connected State */ }
			{ status && status.connected ? (
				<Card>
					<CardBody>
						<div className="notion2wp-connection__connected">
							<div className="notion2wp-connection__connected-info">
								<span className="notion2wp-badge notion2wp-badge--success">
									<span className="notion2wp-badge__dot" />
									{ __( 'Connected', 'notion2wp' ) }
								</span>
								{ status.connection_date && (
									<span className="notion2wp-connection__connected-date">
										{ status.connection_date }
									</span>
								) }
							</div>
							<Button
								variant="secondary"
								isDestructive
								size="small"
								onClick={ () => setShowDisconnectConfirm( true ) }
								isBusy={ loading }
								disabled={ loading }
							>
								{ loading
									? __( 'Disconnecting...', 'notion2wp' )
									: __( 'Disconnect', 'notion2wp' )
								}
							</Button>
						</div>
					</CardBody>
				</Card>
			) : (
				/* Disconnected State - Show Internal Integration Setup */
				<>
					<Card>
						<CardHeader>
							<Flex align="center" justify="space-between">
								<strong>
									{ __( 'Connect to Notion', 'notion2wp' ) }
								</strong>

								{ /* Help Button for Setup Guide */ }
								{ setupGuideShown && (
									<Button
										variant="tertiary"
										icon={ help }
										size="small"
										onClick={ () => setShowSetupGuide( true ) }
									>
										{ __( 'Setup Guide', 'notion2wp' ) }
									</Button>
								) }
							</Flex>
						</CardHeader>
						<CardBody>
							<p className="notion2wp-card__description">
								{ __( 'Connect your Notion workspace using an Internal Integration. This allows the plugin to access pages you share with it.', 'notion2wp' ) }
							</p>

							{ /* Connection Form */ }
							<TextControl
								label={ __( 'Integration Token', 'notion2wp' ) }
								type="password"
								value={ integrationToken }
								onChange={ ( value ) => setIntegrationToken( value ) }
								placeholder={ __( 'Paste your Notion Internal Integration Token here', 'notion2wp' ) }
								required
								help={ __( 'Paste your Internal Integration Token from Notion. Keep this secret!', 'notion2wp' ) }
								className="notion2wp-connection__token-input"
							/>

							<div className="notion2wp-connection__connect-action">
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
					<Card>
						<CardBody>
							<p className="notion2wp-connection__help">
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

export default Connection;
