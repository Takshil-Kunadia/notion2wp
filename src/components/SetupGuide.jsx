/**
 * Setup Guide Component
 *
 * Interactive multi-step guide for helping users configure their Notion integration.
 * Uses the Gutenberg Guide component to provide a step-by-step walkthrough.
 */

import { Guide } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import logo from '../assets/notion2wp-logo.svg';

const SetupGuide = ( { onFinish } ) => {
	const pages = [
		{
			image: (
				<div style={ {
					background: 'linear-gradient(135deg, #fdfbf7 0%, #a29f9b 100%)',
					height: '200px',
					display: 'flex',
					alignItems: 'center',
					justifyContent: 'center',
					marginBottom: '1rem',
				} }>
					<img src={ logo } alt={ __( 'Notion2WP Logo', 'notion2wp' ) } style={ { width: '120px', height: 'auto' } } />
				</div>
			),
			content: (
				<div style={ { padding: '1rem', justifyItems: 'center' } }>
					<h1>{ __( 'Welcome to Notion2WP! 👋', 'notion2wp' ) }</h1>
					<p>
						{ __(
							'This guide will walk you through setting up your Notion integration in just a few minutes.',
							'notion2wp',
						) }
					</p>
					<p>
						{ __(
							'You\'ll be able to import your Notion pages and databases directly into WordPress with automatic conversion to Gutenberg blocks.',
							'notion2wp',
						) }
					</p>
				</div>
			),
		},
		{
			image: (
				<div style={ {
					background: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
					height: '200px',
					display: 'flex',
					alignItems: 'center',
					justifyContent: 'center',
					color: 'white',
					fontSize: '48px',
					marginBottom: '1rem',
				} }>
					🔗
				</div>
			),
			content: (
				<div style={ { padding: '1rem' } }>
					<h2>{ __( 'Step 1: Create a Notion Integration', 'notion2wp' ) }</h2>
					<p>
						{ __(
							'First, you\'ll need to create an Internal Integration in your Notion workspace.',
							'notion2wp',
						) }
					</p>
					<ol style={ { paddingLeft: '1.5rem' } }>
						<li>
							{ __( 'Visit ', 'notion2wp' ) }
							<a
								href="https://www.notion.so/profile/integrations"
								target="_blank"
								rel="noopener noreferrer"
								style={ { color: '#0073aa' } }
							>
								{ __( 'Notion > My Integrations', 'notion2wp' ) }
							</a>
						</li>
						<li>{ __( 'Click "+ New integration"', 'notion2wp' ) }</li>
						<li>{ __( 'Choose "Internal integration" as the type', 'notion2wp' ) }</li>
						<li>{ __( 'Give it a name like "WordPress Import"', 'notion2wp' ) }</li>
						<li>{ __( 'Select an associated workspace', 'notion2wp' ) }</li>
						<li>{ __( 'Click "Save"', 'notion2wp' ) }</li>
					</ol>
				</div>
			),
		},
		{
			image: (
				<div style={ {
					background: 'linear-gradient(135deg, #f093fb 0%, #f5576c 100%)',
					height: '200px',
					display: 'flex',
					alignItems: 'center',
					justifyContent: 'center',
					color: 'white',
					fontSize: '48px',
					marginBottom: '1rem',
				} }>
					⚙️
				</div>
			),
			content: (
				<div style={ { padding: '1rem' } }>
					<h2>{ __( 'Step 2: Configure Capabilities', 'notion2wp' ) }</h2>
					<p>
						{ __(
							'Enable the following capabilities for your integration:',
							'notion2wp',
						) }
					</p>
					<ul style={ { paddingLeft: '1.5rem', listStyleType: 'none' } }>
						<li>☑️ { __( 'Read content (Required)', 'notion2wp' ) }</li>
						<li>☑️ { __( 'Read comments (Optional)', 'notion2wp' ) }</li>
						<li>🔘 { __( 'Read user information without email (Optional)', 'notion2wp' ) }</li>
					</ul>
					<p>
						{ __(
							'Click "Save" to update your integration\'s capabilities.',
							'notion2wp',
						) }
					</p>
				</div>
			),
		},
		{
			image: (
				<div style={ {
					background: 'linear-gradient(135deg, #fa709a 0%, #fee140 100%)',
					height: '200px',
					display: 'flex',
					alignItems: 'center',
					justifyContent: 'center',
					color: 'white',
					fontSize: '48px',
					marginBottom: '1rem',
				} }>
					🔓
				</div>
			),
			content: (
				<div style={ { padding: '1rem' } }>
					<h2>{ __( 'Step 4: Share Pages with Your Integration', 'notion2wp' ) }</h2>
					<p>
						{ __(
							'For the plugin to access your content, you need to share pages with your integration:',
							'notion2wp',
						) }
					</p>
					<ol style={ { paddingLeft: '1.5rem' } }>
						<li>{ __( 'Click on Access Tabs', 'notion2wp' ) }</li>
						<li>{ __( 'Select Teamspaces/Pages you want to be able import onto WordPress', 'notion2wp' ) }</li>
						<li>{ __( 'Click "Save"', 'notion2wp' ) }</li>
					</ol>
					<p style={ { marginTop: '1rem', color: '#50575e', fontSize: '0.9em' } }>
						💡 { __(
							'Tip: You can also share a parent page, and the integration will have access to all child pages.',
							'notion2wp',
						) }
					</p>
				</div>
			),
		},
		{
			image: (
				<div style={ {
					background: 'linear-gradient(135deg, #4facfe 0%, #00f2fe 100%)',
					height: '200px',
					display: 'flex',
					alignItems: 'center',
					justifyContent: 'center',
					color: 'white',
					fontSize: '48px',
					marginBottom: '1rem',
				} }>
					🔑
				</div>
			),
			content: (
				<div style={ { padding: '1rem' } }>
					<h2>{ __( 'Step 3: Copy Your Integration Token', 'notion2wp' ) }</h2>
					<p>
						{ __(
							'After creating the integration, you\'ll see an "Internal Integration Token". Click "Show" and copy it.',
							'notion2wp',
						) }
					</p>
					<p>
						{ __(
							'Paste this token here and click "Connect to Notion".',
							'notion2wp',
						) }
					</p>
					<div style={ {
						background: '#fff3cd',
						border: '1px solid #ffc107',
						borderRadius: '4px',
						padding: '0.75rem',
						marginTop: '1rem',
					} }>
						<strong>⚠️ { __( 'Important:', 'notion2wp' ) }</strong>
						<p style={ { marginBottom: 0, marginTop: '0.5rem' } }>
							{ __(
								'This token should be kept confidential. Never share it publicly!',
								'notion2wp',
							) }
						</p>
					</div>
				</div>
			),
		},
		{
			image: (
				<div style={ {
					background: 'linear-gradient(135deg, #a8edea 0%, #fed6e3 100%)',
					height: '200px',
					display: 'flex',
					alignItems: 'center',
					justifyContent: 'center',
					color: '#333',
					fontSize: '48px',
					marginBottom: '1rem',
				} }>
					🎉
				</div>
			),
			content: (
				<div style={ { padding: '1rem' } }>
					<h2>{ __( 'You\'re All Set!', 'notion2wp' ) }</h2>
					<p>
						{ __(
							'Now you can connect your Notion workspace by pasting your Integration Token in the connection form below.',
							'notion2wp',
						) }
					</p>
					<p>
						{ __(
							'After connecting, you\'ll be able to import your Notion content from the Import tab.',
							'notion2wp',
						) }
					</p>
					<div style={ {
						background: '#d1ecf1',
						border: '1px solid #bee5eb',
						borderRadius: '4px',
						padding: '0.75rem',
						marginTop: '1rem',
					} }>
						<strong>📚 { __( 'Need more help?', 'notion2wp' ) }</strong>
						<p style={ { marginBottom: 0, marginTop: '0.5rem' } }>
							{ __( 'Visit our ', 'notion2wp' ) }
							<a
								href="https://notion2wp.framer.website/"
								target="_blank"
								rel="noopener noreferrer"
								style={ { color: '#0073aa' } }
							>
								{ __( 'documentation', 'notion2wp' ) }
							</a>
							{ __( ' for detailed guides and troubleshooting tips.', 'notion2wp' ) }
						</p>
					</div>
				</div>
			),
		},
	];

	return (
		<Guide
			onFinish={ onFinish }
			pages={ pages }
			contentLabel={ __( 'Notion2WP Setup Guide', 'notion2wp' ) }
			finishButtonText={ __( 'Get Started', 'notion2wp' ) }
		/>
	);
};

export default SetupGuide;
