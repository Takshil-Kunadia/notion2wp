/**
 * Import Modal Component
 *
 * Shows a modal with progress bar during import, and results when complete.
 */

import {
	Button,
	Flex,
	Modal,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';

const ImportModal = ( { importing, importResults, importProgress, onClose, onCancel } ) => {
	if ( ! importing && ! importResults ) {
		return null;
	}

	const { completed, total, currentItem } = importProgress;
	const percent = total > 0 ? Math.round( ( completed / total ) * 100 ) : 0;

	return (
		<Modal
			title={ importing ? __( 'Importing...', 'notion2wp' ) : __( 'Import Complete', 'notion2wp' ) }
			size="medium"
			onRequestClose={ onClose }
			isDismissible={ ! importing }
			shouldCloseOnClickOutside={ ! importing }
			shouldCloseOnEsc={ ! importing }
		>
			{ importing && (
				<div className="notion2wp-import__modal-progress">
					<p className="notion2wp-import__modal-progress-status">
						{ completed + 1 > total
							? __( 'Finishing up...', 'notion2wp' )
							: `${ completed + 1 } / ${ total }`
						}
						{ currentItem && (
							<span className="notion2wp-import__modal-progress-item">
								{ ' — ' }{ currentItem }
							</span>
						) }
					</p>
					<div className="notion2wp-import__progress-track">
						<div
							className="notion2wp-import__progress-bar"
							style={ { width: `${ percent }%` } }
						/>
					</div>
					<Flex justify="flex-end" className="notion2wp-import__modal-actions">
						<Button variant="tertiary" isDestructive onClick={ onCancel }>
							{ __( 'Cancel', 'notion2wp' ) }
						</Button>
					</Flex>
				</div>
			) }

			{ ! importing && importResults && (
				<>
					{ importResults.success && importResults.success.length > 0 && (
						<div className="notion2wp-import__results-section notion2wp-import__results-section--success">
							<h4>
								{ importResults.success.length } { __( 'Post(s) Imported', 'notion2wp' ) }
							</h4>
							<ul>
								{ importResults.success.map( ( result, idx ) => (
									<li key={ idx }>
										<a
											href={ `/wp-admin/post.php?post=${ result.post_id }&action=edit` }
											target="_blank"
											rel="noreferrer"
										>
											{ result.title || ( __( 'Post', 'notion2wp' ) + ' #' + result.post_id ) }
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
										{ result.title && <strong>{ result.title }: </strong> }
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
