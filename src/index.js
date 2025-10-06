import { createRoot, useEffect, useState } from '@wordpress/element';

const apiUrl = window.notion2wpAdmin?.apiUrl || '/wp-json/notion2wp/v1/';
const nonce = window.notion2wpAdmin?.nonce || '';

const NotionAuthAdmin = () => {
	const [status, setStatus] = useState(null);
	const [clientId, setClientId] = useState('');
	const [clientSecret, setClientSecret] = useState('');
	const [loading, setLoading] = useState(false);
	const [message, setMessage] = useState('');
	const [error, setError] = useState('');

	// Fetch connection status on mount
	useEffect( () => {
		fetch(
			`${apiUrl}auth/status`,
			{
				headers: { 'X-WP-Nonce': nonce },
			},
		)
			.then( (res) => res.json() )
			.then( (data) => setStatus( data ) )
			.catch( () => setStatus( null ) );
	}, [] );

	const handleConnect = async (e) => {
		e.preventDefault();
		setLoading( true );
		setError( '' );
		setMessage( '' );
		try {
			const res = await fetch(
				`${apiUrl}auth/connect`,
				{
					method: 'POST',
					headers: {
						'Content-Type': 'application/json',
						'X-WP-Nonce': nonce,
					},
					body: JSON.stringify( { client_id: clientId, client_secret: clientSecret } ),
				},
			);

			const data = await res.json();

			if ( res.ok && data.auth_url ) {
				setMessage('Redirecting to Notion for authorization...');
				window.location.href = data.auth_url;
			} else {
				setError(data.message || 'Failed to start OAuth flow.');
			}
		} catch ( err ) {
			setError( `Connection error. ${err.message}` );
		}
		setLoading( false );
	};

	const handleDisconnect = async () => {
		setLoading( true );
		setError( '' );
		setMessage( '' );
		try {
			const res = await fetch(
				`${apiUrl}auth/disconnect`,
				{
					method: 'DELETE',
					headers: { 'X-WP-Nonce': nonce },
				},
			);

			const data = await res.json();

			if ( res.ok ) {
				setMessage( 'Disconnected from Notion.' );
				setStatus( null );
			} else {
				setError( data.message || 'Failed to disconnect.' );
			}
		} catch ( err ) {
			setError( `Disconnect error. ${err.message}` );
		}

		setLoading( false );
	};

	return (
		<div style={{ maxWidth: 480, margin: '2em auto', background: '#fff', padding: 24, borderRadius: 8, boxShadow: '0 2px 8px #eee' }}>
			<h2>Notion2WP Authentication</h2>
			{status && status.workspace_id ? (
				<>
					<p><strong>Connected to Notion workspace:</strong> {status.workspace_name || status.workspace_id}</p>
					<button onClick={handleDisconnect} disabled={loading} className="button button-secondary">Disconnect</button>
				</>
			) : (
				<form onSubmit={handleConnect}>
					<div style={{ marginBottom: 12 }}>
						<label>Notion Client ID<br />
							<input type="text" value={clientId} onChange={e => setClientId(e.target.value)} className="regular-text" required />
						</label>
					</div>
					<div style={{ marginBottom: 12 }}>
						<label>Notion Client Secret<br />
							<input type="password" value={clientSecret} onChange={e => setClientSecret(e.target.value)} className="regular-text" required />
						</label>
					</div>
					<button type="submit" className="button button-primary" disabled={loading}>Connect to Notion</button>
				</form>
			)}
			{message && <div style={{ color: 'green', marginTop: 16 }}>{message}</div>}
			{error && <div style={{ color: 'red', marginTop: 16 }}>{error}</div>}
		</div>
	);
};

// Mount to admin root if present
const root = document.getElementById( 'notion2wp-admin-root' );
if ( root ) {
	createRoot( root ).render( <NotionAuthAdmin /> );
}
