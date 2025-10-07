/**
 * Notion Import Component
 * 
 * Displays a list of available Notion pages and databases for import.
 * Allows users to select items and import them as WordPress posts.
 * 
 * Features:
 * - Fetches pages/databases from Notion API
 * - Multi-select with bulk actions
 * - Import progress tracking
 * - Detailed success/error reporting
 */

import { useState, useEffect } from '@wordpress/element';
import { 
	Button, 
	Spinner, 
	Notice, 
	CheckboxControl,
	Card,
	CardBody,
	CardHeader,
	Flex,
	FlexItem,
	FlexBlock,
	TabPanel,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { external } from '@wordpress/icons';

const NotionImport = () => {
	// Get localized data from WordPress
	const apiUrl = window.notion2wpAdmin?.apiUrl || '/wp-json/notion2wp/v1/';
	const nonce = window.notion2wpAdmin?.nonce || '';

	// Component state
	const [ items, setItems ] = useState( [] );
	const [ selectedItems, setSelectedItems ] = useState( new Set() );
	const [ loading, setLoading ] = useState( false );
	const [ importing, setImporting ] = useState( false );
	const [ error, setError ] = useState( '' );
	const [ success, setSuccess ] = useState( '' );
	const [ importResults, setImportResults ] = useState( null );
	const [ activeTab, setActiveTab ] = useState( 'all' );

	// Fetch items on component mount
	useEffect( () => {
		fetchItems();
		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [] );

	/**
	 * Fetch available items from Notion
	 */
	const fetchItems = async () => {
		setLoading( true );
		setError( '' );

		try {
			const res = await fetch( `${ apiUrl }import/items`, {
				headers: { 'X-WP-Nonce': nonce },
			} );

			const data = await res.json();

			if ( res.ok ) {
				setItems( data.items || [] );
			} else {
				setError( data.message || __( 'Failed to fetch items from Notion.', 'notion2wp' ) );
			}
		} catch ( err ) {
			setError( __( 'Error fetching items: ', 'notion2wp' ) + err.message );
		}

		setLoading( false );
	};

	/**
	 * Toggle item selection
	 * 
	 * @param {string} itemId - Notion item ID
	 */
	const handleSelectItem = ( itemId ) => {
		const newSelected = new Set( selectedItems );
		if ( newSelected.has( itemId ) ) {
			newSelected.delete( itemId );
		} else {
			newSelected.add( itemId );
		}
		setSelectedItems( newSelected );
	};

	/**
	 * Get items filtered by current tab
	 * 
	 * @returns {Array} Filtered items
	 */
	const getVisibleItems = () => {
		if ( activeTab === 'pages' ) {
			return items.filter( item => item.type === 'page' );
		}
		if ( activeTab === 'databases' ) {
			return items.filter( item => item.type === 'database' );
		}
		return items;
	};

	/**
	 * Select or deselect all visible items
	 */
	const handleSelectAll = () => {
		const visibleItems = getVisibleItems();
		
		// Check if all visible items are selected
		const allSelected = visibleItems.every( item => selectedItems.has( item.id ) );
		
		if ( allSelected ) {
			// Deselect all visible items
			const newSelected = new Set( selectedItems );
			visibleItems.forEach( item => newSelected.delete( item.id ) );
			setSelectedItems( newSelected );
		} else {
			// Select all visible items
			const newSelected = new Set( selectedItems );
			visibleItems.forEach( item => newSelected.add( item.id ) );
			setSelectedItems( newSelected );
		}
	};

	/**
	 * Import selected pages
	 */
	const handleImport = async () => {
		if ( selectedItems.size === 0 ) {
			setError( __( 'Please select at least one item to import.', 'notion2wp' ) );
			return;
		}

		setImporting( true );
		setError( '' );
		setSuccess( '' );
		setImportResults( null );

		try {
			const res = await fetch( `${ apiUrl }import/pages`, {
				method: 'POST',
				headers: {
					'Content-Type': 'application/json',
					'X-WP-Nonce': nonce,
				},
				body: JSON.stringify( {
					page_ids: Array.from( selectedItems ),
				} ),
			} );

			const data = await res.json();

			if ( res.ok ) {
				setSuccess( data.message || __( 'Import completed successfully!', 'notion2wp' ) );
				setImportResults( data );
				setSelectedItems( new Set() );
			} else {
				setError( data.message || __( 'Import failed.', 'notion2wp' ) );
			}
		} catch ( err ) {
			setError( __( 'Import error: ', 'notion2wp' ) + err.message );
		}

		setImporting( false );
	};

	// Calculate counts for tabs
	const pagesCount = items.filter( item => item.type === 'page' ).length;
	const databasesCount = items.filter( item => item.type === 'database' ).length;
	const visibleItems = getVisibleItems();
	const allVisibleSelected = visibleItems.length > 0 && 
		visibleItems.every( item => selectedItems.has( item.id ) );

	/**
	 * Render individual item row
	 * 
	 * @param {Object} item - Notion item
	 * @returns {JSX.Element} Table row
	 */
	const renderItemRow = ( item ) => (
		<tr key={ item.id } style={ { opacity: item.archived ? 0.6 : 1 } }>
			<td style={ { width: '40px' } }>
				<CheckboxControl
					checked={ selectedItems.has( item.id ) }
					onChange={ () => handleSelectItem( item.id ) }
					aria-label={ __( 'Select', 'notion2wp' ) + ' ' + item.title }
				/>
			</td>
			<td>
				<Flex align="flex-start" gap={ 2 }>
					<FlexBlock>
						<strong>{ item.title }</strong>
						{ item.archived && (
							<span style={ {
								marginLeft: '8px',
								color: '#757575',
								fontSize: '12px',
								fontStyle: 'italic',
							} }>
								({ __( 'Archived', 'notion2wp' ) })
							</span>
						) }
						{ item.type === 'database' && item.description && (
							<div style={ { 
								color: '#757575',
								fontSize: '13px',
								marginTop: '4px',
							} }>
								{ item.description }
							</div>
						) }
					</FlexBlock>
				</Flex>
			</td>
			{ item.type === 'database' && activeTab !== 'pages' && (
				<td style={ { fontSize: '12px', color: '#50575e' } }>
					{ item.properties && item.properties.length > 0 ? (
						<>{ item.properties.length } { __( 'properties', 'notion2wp' ) }</>
					) : (
						'-'
					) }
				</td>
			) }
			<td style={ { fontSize: '13px', color: '#50575e' } }>
				{ new Date( item.last_edited_time ).toLocaleString() }
			</td>
			<td>
				<Button
					href={ item.url }
					target="_blank"
					rel="noopener noreferrer"
					variant="link"
					icon={ external }
					iconSize={ 16 }
					iconPosition="right"
				>
					{ __( 'View in Notion', 'notion2wp' ) }
				</Button>
			</td>
		</tr>
	);

	return (
		<div style={ { maxWidth: '1400px' } }>
			<Flex justify="space-between" align="flex-start" style={ { marginBottom: '1.5rem' } }>
				<FlexBlock>
					<h1 style={ { margin: 0 } }>
						{ __( 'Import from Notion', 'notion2wp' ) }
					</h1>
					<p style={ { marginTop: '0.5rem', color: '#50575e' } }>
						{ __( 'Select pages or databases from your Notion workspace to import as WordPress posts.', 'notion2wp' ) }
					</p>
				</FlexBlock>
				<FlexItem>
					<Button
						variant="secondary"
						onClick={ fetchItems }
						isBusy={ loading }
						disabled={ loading }
						icon={ loading ? undefined : 'update' }
					>
						{ loading ? __( 'Refreshing...', 'notion2wp' ) : __( 'Refresh List', 'notion2wp' ) }
					</Button>
				</FlexItem>
			</Flex>

			{ /* Error/Success Messages */ }
			{ error && (
				<Notice status="error" isDismissible onRemove={ () => setError( '' ) }>
					{ error }
				</Notice>
			) }

			{ success && (
				<Notice status="success" isDismissible onRemove={ () => setSuccess( '' ) }>
					{ success }
				</Notice>
			) }

			{ /* Import Results */ }
			{ importResults && (
				<Card style={ { marginBottom: '1.5rem' } }>
					<CardHeader>
						<strong>{ __( 'Import Results', 'notion2wp' ) }</strong>
					</CardHeader>
					<CardBody>
						{ importResults.success && importResults.success.length > 0 && (
							<div style={ { marginBottom: importResults.errors?.length > 0 ? '1.5rem' : 0 } }>
								<h4 style={ { color: '#00a32a', marginTop: 0 } }>
									✓ { __( 'Successfully Imported', 'notion2wp' ) } ({ importResults.success.length })
								</h4>
								<ul style={ { marginBottom: 0 } }>
									{ importResults.success.map( ( result ) => (
										<li key={ result.page_id }>
											<a
												href={ `/wp-admin/post.php?post=${ result.post_id }&action=edit` }
												target="_blank"
												rel="noreferrer"
											>
												{ __( 'Post ID:', 'notion2wp' ) } { result.post_id }
											</a>
											{ ' ' }
											<span style={ { color: '#757575', fontSize: '12px' } }>
												({ result.page_id })
											</span>
										</li>
									) ) }
								</ul>
							</div>
						) }

						{ importResults.errors && importResults.errors.length > 0 && (
							<div>
								<h4 style={ { color: '#d63638', marginTop: 0 } }>
									✗ { __( 'Failed', 'notion2wp' ) } ({ importResults.errors.length })
								</h4>
								<ul style={ { marginBottom: 0 } }>
									{ importResults.errors.map( ( result, idx ) => (
										<li key={ idx } style={ { color: '#d63638' } }>
											<code>{ result.page_id }</code>: { result.message }
										</li>
									) ) }
								</ul>
							</div>
						) }
					</CardBody>
				</Card>
			) }

			{ /* Main Content */ }
			{ loading ? (
				<Card>
					<CardBody>
						<Flex align="center" justify="center" style={ { padding: '3rem' } }>
							<Spinner />
							<span style={ { marginLeft: '1rem' } }>
								{ __( 'Loading items from Notion...', 'notion2wp' ) }
							</span>
						</Flex>
					</CardBody>
				</Card>
			) : items.length === 0 ? (
				<Card>
					<CardBody>
						<div style={ { textAlign: 'center', padding: '2rem' } }>
							<h3>{ __( 'No Items Found', 'notion2wp' ) }</h3>
							<p style={ { color: '#757575' } }>
								{ __( 'No pages or databases found in your Notion workspace.', 'notion2wp' ) }
								<br />
								{ __( 'Make sure you\'re connected and have shared pages with your integration.', 'notion2wp' ) }
							</p>
						</div>
					</CardBody>
				</Card>
			) : (
				<Card>
					<CardHeader>
						<Flex justify="space-between" align="center">
							<FlexBlock>
								<strong>
									{ __( 'Available Items', 'notion2wp' ) } ({ items.length })
								</strong>
							</FlexBlock>
							<FlexItem>
								<Flex gap={ 2 }>
									<Button
										variant="link"
										onClick={ handleSelectAll }
										disabled={ visibleItems.length === 0 }
									>
										{ allVisibleSelected
											? __( 'Deselect All', 'notion2wp' )
											: __( 'Select All', 'notion2wp' ) }
									</Button>
									<Button
										variant="primary"
										onClick={ handleImport }
										isBusy={ importing }
										disabled={ importing || selectedItems.size === 0 }
									>
										{ importing
											? __( 'Importing...', 'notion2wp' )
											: selectedItems.size > 0
												? __( 'Import Selected', 'notion2wp' ) + ` (${ selectedItems.size })`
												: __( 'Import Selected', 'notion2wp' ) }
									</Button>
								</Flex>
							</FlexItem>
						</Flex>
					</CardHeader>
					<CardBody style={ { padding: 0 } }>
						<TabPanel
							className="notion2wp-import-tabs"
							activeClass="is-active"
							onSelect={ ( tabName ) => setActiveTab( tabName ) }
							tabs={ [
								{
									name: 'all',
									title: __( 'All', 'notion2wp' ) + ` (${ items.length })`,
								},
								{
									name: 'pages',
									title: __( 'Pages', 'notion2wp' ) + ` (${ pagesCount })`,
								},
								{
									name: 'databases',
									title: __( 'Databases', 'notion2wp' ) + ` (${ databasesCount })`,
								},
							] }
						>
							{ () => (
								<div style={ { padding: '1rem' } }>
									{ visibleItems.length === 0 ? (
										<div style={ { textAlign: 'center', padding: '2rem', color: '#757575' } }>
											{ __( 'No items in this category.', 'notion2wp' ) }
										</div>
									) : (
										<table className="wp-list-table widefat fixed striped">
											<thead>
												<tr>
													<th style={ { width: '50px' } }>
														{ __( 'Select', 'notion2wp' ) }
													</th>
													<th>{ __( 'Title', 'notion2wp' ) }</th>
													{ activeTab === 'databases' && (
														<th style={ { width: '120px' } }>
															{ __( 'Properties', 'notion2wp' ) }
														</th>
													) }
													<th style={ { width: '180px' } }>
														{ __( 'Last Edited', 'notion2wp' ) }
													</th>
													<th style={ { width: '150px' } }>
														{ __( 'Actions', 'notion2wp' ) }
													</th>
												</tr>
											</thead>
											<tbody>
												{ visibleItems.map( renderItemRow ) }
											</tbody>
										</table>
									) }
								</div>
							) }
						</TabPanel>
					</CardBody>
				</Card>
			) }
		</div>
	);
};

export default NotionImport;
