/**
 * Import Modal Component
 *
 * Shows a modal with a spinner during import, and results when complete.
 */

import {
	Button,
	Spinner,
	Flex,
	Modal,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';

const ImportModal = ( { importing, importResults, onClose } ) => {
	if ( ! importing && ! importResults ) {
		return null;
	}

	return (
		<Modal
			title={ importing ? __( 'Importing...', 'notion2wp' ) : __( 'Import Complete', 'notion2wp' ) }
			onRequestClose={ onClose }
			isDismissible={ ! importing }
			shouldCloseOnClickOutside={ ! importing }
			shouldCloseOnEsc={ ! importing }
		>
			{ importing && (
				<Flex align="center" justify="center" className="notion2wp-import__modal-spinner">
					<Spinner />
					<span className="notion2wp-import__modal-spinner-text">
						{ __( 'Importing selected items. This may take a few moments...', 'notion2wp' ) }
					</span>
				</Flex>
			) }

			{ ! importing && importResults && (
				<>
					{ importResults.success && importResults.success.length > 0 && (
						<div className="notion2wp-import__results-section notion2wp-import__results-section--success">
							<h4>
								{ importResults.success.length } { __( 'post(s) imported', 'notion2wp' ) }
							</h4>
							<ul>
								{ importResults.success.map( ( result ) => (
									<li key={ result.post_id }>
										<a
											href={ `/wp-admin/post.php?post=${ result.post_id }&action=edit` }
											target="_blank"
											rel="noreferrer"
										>
											{ __( 'Post', 'notion2wp' ) } #{ result.post_id }
										</a>
									</li>
								) ) }
							</ul>
						</div>
					) }

					{ importResults.errors && importResults.errors.length > 0 && (
						<div className="notion2wp-import__results-section notion2wp-import__results-section--error">
							<h4>
								{ importResults.errors.length } { __( 'failed', 'notion2wp' ) }
							</h4>
							<ul>
								{ importResults.errors.map( ( result, idx ) => (
									<li key={ idx }>
										{ result.message }
									</li>
								) ) }
							</ul>
						</div>
					) }

					<Flex justify="flex-end" className="notion2wp-import__modal-actions">
						<Button variant="primary" onClick={ onClose }>
							{ __( 'Close', 'notion2wp' ) }
						</Button>
					</Flex>
				</>
			) }
		</Modal>
	);
};

export default ImportModal;
