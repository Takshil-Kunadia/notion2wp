/**
 * Notion Import Component
 *
 * Displays a list of available Notion pages, databases and Data Sources for import.
 * Allows users to select items and import them as WordPress posts.
 *
 * Features:
 * - Fetches pages/databases/datasources from Notion API
 * - Multi-select with bulk actions
 * - Import progress tracking
 * - Detailed success/error reporting
 */

import { useState, useEffect, useMemo } from '@wordpress/element';
import {
	Button,
	Spinner,
	Card,
	CardBody,
	Flex,
	FlexItem,
	FlexBlock,
	Snackbar,
} from '@wordpress/components';
import { DataViews, filterSortAndPaginate } from '@wordpress/dataviews/wp';
import { __ } from '@wordpress/i18n';
import { external } from '@wordpress/icons';
import notionLogo from '../assets/sync-content-from-notion-logo.svg';

const Import = () => {
	// Get localized data from WordPress
	const apiUrl = window.syncContentFromNotionAdmin?.apiUrl || '/wp-json/sync-content-from-notion/v1/';
	const nonce = window.syncContentFromNotionAdmin?.nonce || '';

	// Placeholder image
	const placeholderImage = notionLogo;

	// Component state
	const [ items, setItems ] = useState( [] );
	const [ loading, setLoading ] = useState( false );
	const [ importing, setImporting ] = useState( false );
	const [ error, setError ] = useState( '' );
	const [ success, setSuccess ] = useState( '' );
	const [ importResults, setImportResults ] = useState( null );

	// DataViews state
	const [ view, setView ] = useState( {
		type: 'table',
		search: '',
		filters: [],
		page: 1,
		perPage: 10,
		sort: {
			field: 'last_edited_time',
			direction: 'desc',
		},
		fields: [ 'type', 'properties', 'last_edited_time' ],
		titleField: 'title',
		mediaField: 'media',
		layout: {
			styles: {
				satellites: {
					align: 'end',
				},
			},
		},
	} );

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
				setError( data.message || __( 'Failed to fetch items from Notion.', 'sync-content-from-notion' ) );
			}
		} catch ( err ) {
			setError( __( 'Error fetching items: ', 'sync-content-from-notion' ) + err.message );
		}

		setView( prevView => ( { ...prevView } ) );
		setLoading( false );
	};

	/**
	 * Import selected pages
	 *
	 * @param {Array} selectedItems - Array of selected item objects
	 */
	const handleImport = async ( selectedItems ) => {
		if ( ! selectedItems || selectedItems.length === 0 ) {
			setError( __( 'Please select at least one item to import.', 'sync-content-from-notion' ) );
			return;
		}

		setImporting( true );
		setError( '' );
		setSuccess( '' );
		setImportResults( null );

		try {
			const items = selectedItems.map( item => ( {
				id: item.id,
				type: item.type,
			} ) );

			const res = await fetch( `${ apiUrl }import/pages`, {
				method: 'POST',
				headers: {
					'Content-Type': 'application/json',
					'X-WP-Nonce': nonce,
				},
				body: JSON.stringify( {
					items: items,
				} ),
			} );

			const data = await res.json();

			if ( data.success.length > 0 ) {
				setSuccess( data.message || __( 'Import completed successfully!', 'sync-content-from-notion' ) );
				setImportResults( data );
			} else if ( data.errors && data.errors.length > 0 ) {
				setImportResults( data );
				setError( data.errors[0].message || __( 'Import failed.', 'sync-content-from-notion' ) );
			} else {
				setError( data.message || __( 'Import failed.', 'sync-content-from-notion' ) );
			}
		} catch ( err ) {
			setError( __( 'Import error: ', 'sync-content-from-notion' ) + err.message );
		}

		setImporting( false );
		clearImportResults();
	};

	/**
	 * Clear import results after a delay
	 */
	const clearImportResults = () => {
		setTimeout(() => {
			setImportResults( null );
			setError( '' );
			setSuccess( '' );
		}, 5000);
	};

	/**
	 * DataViews fields configuration
	 */
	const fields = useMemo( () => [
		{
			id: 'title',
			label: __( 'Title', 'sync-content-from-notion' ),
			enableGlobalSearch: true,
			enableSorting: false,
			isVisible: true,
			render: ( { item } ) => {
				return (
					<Flex gap={ 2 } align="flex-start">
						<FlexBlock>
							<strong>{ item.title || __( 'Untitled', 'sync-content-from-notion' ) }</strong>
							{ item.archived && (
								<span style={{
									marginLeft: '8px',
									color: '#757575',
									fontSize: '12px',
									fontStyle: 'italic',
								}}>
									({ __( 'Archived', 'sync-content-from-notion' ) })
								</span>
							) }
							{ item.type === 'database' && item.description && (
								<div style={{
									color: '#757575',
									fontSize: '13px',
									marginTop: '4px',
								}}>
									{ item.description }
								</div>
							) }
						</FlexBlock>
					</Flex>
				);
			},
		},
		{
			id: 'media',
			label: __( 'Media', 'sync-content-from-notion' ),
			isVisible: false,
			type: 'media',
			render: ( { item } ) => {
				return item.media ? (
					<img src={ item.media } alt={ __( 'Media', 'sync-content-from-notion' ) } style={{ maxWidth: '100px' }} />
				) : (
					<img src={ placeholderImage } alt={ __( 'Site Logo', 'sync-content-from-notion' ) } style={{ maxWidth: '100px' }} />
				);
			},
		},
		{
			id: 'type',
			label: __( 'Type', 'sync-content-from-notion' ),
			elements: [
				{ value: 'page', label: __( 'Page', 'sync-content-from-notion' ) },
				{ value: 'database', label: __( 'Database', 'sync-content-from-notion' ) },
				{ value: 'data_source', label: __( 'Data Source', 'sync-content-from-notion' ) },
			],
			isVisible: false,
			filterBy: {
				operators: [ 'isAny' ],
			},
			enableSorting: true,
			render: ( { item } ) => {
				return item.type === 'page'
					? __( 'Page', 'sync-content-from-notion' )
					: item.type === 'database'
						? __( 'Database', 'sync-content-from-notion' )
						: __( 'Data Source', 'sync-content-from-notion' );
			},
		},
		{
			id: 'properties',
			label: __( 'Properties', 'sync-content-from-notion' ),
			isVisible: true,
			enableSorting: false,
			render: ( { item } ) => {
				if ( item.type !== 'database' || ! item.properties ) {
					return '-';
				}
				return `${ item.properties.length } ${ __( 'properties', 'sync-content-from-notion' ) }`;
			},
		},
		{
			id: 'last_edited_time',
			label: __( 'Last Edited', 'sync-content-from-notion' ),
			isVisible: true,
			enableSorting: true,
			render: ( { item } ) => {
				return new Date( item.last_edited_time ).toLocaleString();
			},
		},
		// eslint-disable-next-line react-hooks/exhaustive-deps
	], [] );

	/**
	 * DataViews actions configuration
	 */
	const actions = useMemo( () => [
		{
			id: 'import',
			label: __( 'Import to WordPress', 'sync-content-from-notion' ),
			isPrimary: true,
			icon: 'download',
			supportsBulk: true,
			callback: async ( selectedItems ) => {
				await handleImport( selectedItems );
			},
		},
		{
			id: 'view_notion',
			label: __( 'View in Notion', 'sync-content-from-notion' ),
			variant: 'secondary',
			icon: external,
			supportsBulk: false,
			callback: ( selectedItems ) => {
				if ( selectedItems.length === 1 ) {
					const item = selectedItems[0];
					window.open( item.url, '_blank', 'noopener noreferrer' );
				}
			},
		},
		// eslint-disable-next-line react-hooks/exhaustive-deps
	], [] );

	const { data: processedData, paginationInfo } = useMemo( () => {
		return filterSortAndPaginate( items, view, fields );
		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [ view ] );

	return (
		<div className="sync-content-from-notion-import">
			<Flex justify="space-between" align="flex-start" style={{ marginBottom: '1.5rem' }}>
				<FlexBlock>
					<p style={{ margin: 0, color: '#50575e', fontSize: '14px' }}>
						{ __( 'Select pages, databases or data sources from your Notion workspace to import as WordPress posts.', 'sync-content-from-notion' ) }
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
						{ loading ? __( 'Refreshing...', 'sync-content-from-notion' ) : __( 'Refresh List', 'sync-content-from-notion' ) }
					</Button>
				</FlexItem>
			</Flex>

			{ /* Importing Notice */ }
			{ importing && (
				<div style={{ marginBottom: '1.5rem' }}>
					<Snackbar status="info">
						{ __( 'Importing selected items. This may take a few moments...', 'sync-content-from-notion' ) }
					</Snackbar>
				</div>
			) }

			{ /* Import Results */ }
			{ importResults && (
				<Card style={{ marginBottom: '1.5rem' }}>
					<CardBody>
						{ success && importResults.success && importResults.success.length > 0 && (
							<div style={{ marginBottom: importResults.errors?.length > 0 ? '1.5rem' : 0 }}>
								<h4 style={{ color: '#00a32a', marginTop: 0 }}>
									✓ { __( 'Successfully Imported', 'sync-content-from-notion' ) } ({ importResults.success.length })
								</h4>
								<ul style={{ marginBottom: 0 }}>
									{ importResults.success.map( ( result ) => (
										<li key={ result.page_id }>
											<a
												href={ `/wp-admin/post.php?post=${ result.post_id }&action=edit` }
												target="_blank"
												rel="noreferrer"
											>
												{ __( 'Post ID:', 'sync-content-from-notion' ) } { result.post_id }
											</a>
											{ ' ' }
											<span style={{ color: '#757575', fontSize: '12px' }}>
												({ result.page_id })
											</span>
										</li>
									) ) }
								</ul>
							</div>
						) }

						{ error && importResults.errors && importResults.errors.length > 0 && (
							<div>
								<h4 style={{ color: '#d63638', marginTop: 0 }}>
									✗ { __( 'Failed', 'sync-content-from-notion' ) } ({ importResults.errors.length })
								</h4>
								<ul style={{ marginBottom: 0 }}>
									{ importResults.errors.map( ( result, idx ) => (
										<li key={ idx } style={{ color: '#d63638' }}>
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
						<Flex align="center" justify="center" style={{ padding: '3rem' }}>
							<Spinner />
							<span style={{ marginLeft: '1rem' }}>
								{ __( 'Loading items from Notion...', 'sync-content-from-notion' ) }
							</span>
						</Flex>
					</CardBody>
				</Card>
			) : items.length === 0 ? (
				<Card>
					<CardBody>
						<div style={{ textAlign: 'center', padding: '2rem' }}>
							<h3>{ __( 'No Items Found', 'sync-content-from-notion' ) }</h3>
							<p style={{ color: '#757575' }}>
								{ __( 'No pages, databases or data sources found in your Notion workspace.', 'sync-content-from-notion' ) }
								<br />
								{ __( 'Make sure you\'re connected and have shared pages with your integration.', 'sync-content-from-notion' ) }
								<br />
								{ __( 'Switch to the settings tab above to establish a connection.', 'sync-content-from-notion' ) }
							</p>
						</div>
					</CardBody>
				</Card>
			) : (
				<DataViews
					data={ processedData }
					fields={ fields }
					view={ view }
					onChangeView={ setView }
					actions={ actions }
					paginationInfo={ paginationInfo }
					defaultLayouts={{ table: {}, grid: {}, list: {} }}
				/>
			) }
		</div>
	);
};

export default Import;
