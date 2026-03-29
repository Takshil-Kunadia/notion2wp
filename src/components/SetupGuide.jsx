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
				<div className="notion2wp-guide__hero notion2wp-guide__hero--welcome">
					<img
						src={ logo }
						alt={ __( 'Notion2WP Logo', 'notion2wp' ) }
						className="notion2wp-guide__hero-logo"
					/>
				</div>
			),
			content: (
				<div className="notion2wp-guide__content" style={ { textAlign: 'center' } }>
					<h1>{ __( 'Welcome to Notion2WP', 'notion2wp' ) }</h1>
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
				<div className="notion2wp-guide__hero notion2wp-guide__hero--integration">
					<span className="notion2wp-guide__hero-icon">1</span>
				</div>
			),
			content: (
				<div className="notion2wp-guide__content">
					<h2>{ __( 'Create a Notion Integration', 'notion2wp' ) }</h2>
					<p>
						{ __(
							'First, you\'ll need to create an Internal Integration in your Notion workspace.',
							'notion2wp',
						) }
					</p>
					<ol>
						<li>
							{ __( 'Visit ', 'notion2wp' ) }
							<a
								href="https://www.notion.so/profile/integrations"
								target="_blank"
								rel="noopener noreferrer"
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
				<div className="notion2wp-guide__hero notion2wp-guide__hero--capabilities">
					<span className="notion2wp-guide__hero-icon">2</span>
				</div>
			),
			content: (
				<div className="notion2wp-guide__content">
					<h2>{ __( 'Configure Capabilities', 'notion2wp' ) }</h2>
					<p>
						{ __(
							'Enable the following capabilities for your integration:',
							'notion2wp',
						) }
					</p>
					<ul style={ { listStyleType: 'none', paddingLeft: 0 } }>
						<li>{ __( 'Read content (Required)', 'notion2wp' ) }</li>
						<li>{ __( 'Read comments (Optional)', 'notion2wp' ) }</li>
						<li>{ __( 'Read user information without email (Optional)', 'notion2wp' ) }</li>
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
				<div className="notion2wp-guide__hero notion2wp-guide__hero--share">
					<span className="notion2wp-guide__hero-icon">3</span>
				</div>
			),
			content: (
				<div className="notion2wp-guide__content">
					<h2>{ __( 'Share Pages with Your Integration', 'notion2wp' ) }</h2>
					<p>
						{ __(
							'For the plugin to access your content, you need to share pages with your integration:',
							'notion2wp',
						) }
					</p>
					<ol>
						<li>{ __( 'Click on Access Tabs', 'notion2wp' ) }</li>
						<li>{ __( 'Select Teamspaces/Pages you want to be able import onto WordPress', 'notion2wp' ) }</li>
						<li>{ __( 'Click "Save"', 'notion2wp' ) }</li>
					</ol>
					<div className="notion2wp-guide__callout notion2wp-guide__callout--tip">
						<p>
							{ __(
								'Tip: You can also share a parent page, and the integration will have access to all child pages.',
								'notion2wp',
							) }
						</p>
					</div>
				</div>
			),
		},
		{
			image: (
				<div className="notion2wp-guide__hero notion2wp-guide__hero--token">
					<span className="notion2wp-guide__hero-icon">4</span>
				</div>
			),
			content: (
				<div className="notion2wp-guide__content">
					<h2>{ __( 'Copy Your Integration Token', 'notion2wp' ) }</h2>
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
					<div className="notion2wp-guide__callout notion2wp-guide__callout--warning">
						<strong>{ __( 'Important:', 'notion2wp' ) }</strong>
						<p>
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
				<div className="notion2wp-guide__hero notion2wp-guide__hero--complete">
					<span className="notion2wp-guide__hero-icon">5</span>
				</div>
			),
			content: (
				<div className="notion2wp-guide__content">
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
					<div className="notion2wp-guide__callout notion2wp-guide__callout--info">
						<strong>{ __( 'Need more help?', 'notion2wp' ) }</strong>
						<p>
							{ __( 'Visit our ', 'notion2wp' ) }
							<a
								href="https://notion2wp.framer.website/"
								target="_blank"
								rel="noopener noreferrer"
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
