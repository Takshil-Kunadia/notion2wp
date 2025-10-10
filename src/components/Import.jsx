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
import { DataViews } from '@wordpress/dataviews/wp';
import { __ } from '@wordpress/i18n';
import { external } from '@wordpress/icons';

const Import = () => {
	// Get localized data from WordPress
	const apiUrl = window.notion2wpAdmin?.apiUrl || '/wp-json/notion2wp/v1/';
	const nonce = window.notion2wpAdmin?.nonce || '';
	const siteLogo = window.notion2wpAdmin?.siteLogo || '';

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
		perPage: 20,
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
				setError( data.message || __( 'Failed to fetch items from Notion.', 'notion2wp' ) );
			}
		} catch ( err ) {
			setError( __( 'Error fetching items: ', 'notion2wp' ) + err.message );
		}

		setLoading( false );
	};

	/**
	 * Import selected pages
	 *
	 * @param {Array} selectedItems - Array of selected item objects
	 */
	const handleImport = async ( selectedItems ) => {
		if ( ! selectedItems || selectedItems.length === 0 ) {
			setError( __( 'Please select at least one item to import.', 'notion2wp' ) );
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
				setSuccess( data.message || __( 'Import completed successfully!', 'notion2wp' ) );
				setImportResults( data );
			} else if ( data.errors && data.errors.length > 0 ) {
				setImportResults( data );
				setError( data.errors[0].message || __( 'Import failed.', 'notion2wp' ) );
			} else {
				setError( data.message || __( 'Import failed.', 'notion2wp' ) );
			}
		} catch ( err ) {
			setError( __( 'Import error: ', 'notion2wp' ) + err.message );
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
	 * Handle view change
	 */
	const onChangeView = ( newView ) => {
		// TODO: Update items as per the new view.
		setView( newView );
	};

	/**
	 * DataViews fields configuration
	 */
	const fields = useMemo( () => [
		{
			id: 'title',
			label: __( 'Title', 'notion2wp' ),
			enableGlobalSearch: true,
			enableSorting: false,
			isVisible: true,
			render: ( { item } ) => {
				return (
					<Flex gap={ 2 } align="flex-start">
						<FlexBlock>
							<strong>{ item.title || __( 'Untitled', 'notion2wp' ) }</strong>
							{ item.archived && (
								<span style={{
									marginLeft: '8px',
									color: '#757575',
									fontSize: '12px',
									fontStyle: 'italic',
								}}>
									({ __( 'Archived', 'notion2wp' ) })
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
			label: __( 'Media', 'notion2wp' ),
			isVisible: false,
			type: 'media',
			render: ( { item } ) => {
				return item.media ? (
					<img src={ item.media } alt={ __( 'Media', 'notion2wp' ) } style={{ maxWidth: '100px' }} />
				) : (
					<img src={ siteLogo } alt={ __( 'Site Logo', 'notion2wp' ) } style={{ maxWidth: '100px' }} />
				);
			},
		},
		{
			id: 'type',
			label: __( 'Type', 'notion2wp' ),
			elements: [
				{ value: 'page', label: __( 'Page', 'notion2wp' ) },
				{ value: 'database', label: __( 'Database', 'notion2wp' ) },
				{ value: 'data_source', label: __( 'Data Source', 'notion2wp' ) },
			],
			isVisible: false,
			filterBy: {
				operators: [ 'isAny' ],
			},
			enableSorting: true,
			render: ( { item } ) => {
				return item.type === 'page'
					? __( 'Page', 'notion2wp' )
					: item.type === 'database'
						? __( 'Database', 'notion2wp' )
						: __( 'Data Source', 'notion2wp' );
			},
		},
		{
			id: 'properties',
			label: __( 'Properties', 'notion2wp' ),
			isVisible: true,
			enableSorting: false,
			render: ( { item } ) => {
				if ( item.type !== 'database' || ! item.properties ) {
					return '-';
				}
				return `${ item.properties.length } ${ __( 'properties', 'notion2wp' ) }`;
			},
		},
		{
			id: 'last_edited_time',
			label: __( 'Last Edited', 'notion2wp' ),
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
			label: __( 'Import to WordPress', 'notion2wp' ),
			isPrimary: true,
			icon: 'download',
			supportsBulk: true,
			callback: async ( selectedItems ) => {
				await handleImport( selectedItems );
			},
		},
		{
			id: 'view_notion',
			label: __( 'View in Notion', 'notion2wp' ),
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

	/**
	 * Calculate pagination info
	 */
	const paginationInfo = useMemo( () => {
		return {
			totalItems: items.length,
			totalPages: Math.ceil( items.length / view.perPage ),
		};
	}, [ items.length, view.perPage ] );

	return (
		<div style={{ maxWidth: '1400px' }}>
			<Flex justify="space-between" align="flex-start" style={{ marginBottom: '1.5rem' }}>
				<FlexBlock>
					<h1 style={{ margin: 0 }}>
						{ __( 'Import from Notion', 'notion2wp' ) }
					</h1>
					<p style={{ marginTop: '0.5rem', color: '#50575e' }}>
						{ __( 'Select pages, databases or data sources from your Notion workspace to import as WordPress posts.', 'notion2wp' ) }
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

			{ /* Importing Notice */ }
			{ importing && (
				<div style={{ marginBottom: '1.5rem' }}>
					<Snackbar status="info">
						{ __( 'Importing selected items. This may take a few moments...', 'notion2wp' ) }
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
									✓ { __( 'Successfully Imported', 'notion2wp' ) } ({ importResults.success.length })
								</h4>
								<ul style={{ marginBottom: 0 }}>
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
									✗ { __( 'Failed', 'notion2wp' ) } ({ importResults.errors.length })
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
								{ __( 'Loading items from Notion...', 'notion2wp' ) }
							</span>
						</Flex>
					</CardBody>
				</Card>
			) : items.length === 0 ? (
				<Card>
					<CardBody>
						<div style={{ textAlign: 'center', padding: '2rem' }}>
							<h3>{ __( 'No Items Found', 'notion2wp' ) }</h3>
							<p style={{ color: '#757575' }}>
								{ __( 'No pages, databases or data sources found in your Notion workspace.', 'notion2wp' ) }
								<br />
								{ __( 'Make sure you\'re connected and have shared pages with your integration.', 'notion2wp' ) }
							</p>
						</div>
					</CardBody>
				</Card>
			) : (
				<DataViews
					data={ items }
					fields={ fields }
					view={ view }
					onChangeView={ onChangeView }
					actions={ actions }
					paginationInfo={ paginationInfo }
					defaultLayouts={{ table: {}, grid: {}, list: {} }}
				/>
			) }
		</div>
	);
};

export default Import;
