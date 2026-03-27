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

import { useState, useEffect, useMemo, useRef } from '@wordpress/element';
import {
	Button,
	Spinner,
	Card,
	CardBody,
	Flex,
	FlexItem,
	FlexBlock,
} from '@wordpress/components';
import { DataViews, filterSortAndPaginate } from '@wordpress/dataviews/wp';
import { __ } from '@wordpress/i18n';
import { external } from '@wordpress/icons';
import notionLogo from '../assets/notion2wp-logo.svg';
import ImportModal from './ImportModal';

const Import = () => {
	// Get localized data from WordPress
	const apiUrl = window.notion2wpAdmin?.apiUrl || '/wp-json/notion2wp/v1/';
	const nonce = window.notion2wpAdmin?.nonce || '';

	// Placeholder image
	const placeholderImage = notionLogo;

	// Component state
	const [ items, setItems ] = useState( [] );
	const [ loading, setLoading ] = useState( false );
	const [ importing, setImporting ] = useState( false );
	const [ error, setError ] = useState( '' );
	const [ importResults, setImportResults ] = useState( null );
	const [ importProgress, setImportProgress ] = useState( { completed: 0, total: 0, currentItem: '' } );
	const cancelledRef = useRef( false );

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
				setError( data.message || __( 'Failed to fetch items from Notion.', 'notion2wp' ) );
			}
		} catch ( err ) {
			setError( __( 'Error fetching items: ', 'notion2wp' ) + err.message );
		}

		setView( prevView => ( { ...prevView } ) );
		setLoading( false );
	};

	/**
	 * Import selected items one at a time with progress tracking.
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
		setImportResults( null );
		cancelledRef.current = false;

		const total = selectedItems.length;
		setImportProgress( { completed: 0, total, currentItem: '' } );

		const results = { success: [], errors: [] };

		for ( let i = 0; i < selectedItems.length; i++ ) {
			if ( cancelledRef.current ) {
				break;
			}

			const item = selectedItems[ i ];
			const itemTitle = item.title || __( 'Untitled', 'notion2wp' );

			setImportProgress( { completed: i, total, currentItem: itemTitle } );

			try {
				const res = await fetch( `${ apiUrl }import/pages`, {
					method: 'POST',
					headers: {
						'Content-Type': 'application/json',
						'X-WP-Nonce': nonce,
					},
					body: JSON.stringify( {
						items: [ { id: item.id, type: item.type } ],
					} ),
				} );

				const data = await res.json();

				if ( data.success && data.success.length > 0 ) {
					data.success.forEach( ( entry ) => {
						results.success.push( { ...entry, title: entry.title || itemTitle } );
					} );
				}

				if ( data.errors && data.errors.length > 0 ) {
					data.errors.forEach( ( entry ) => {
						results.errors.push( { ...entry, title: entry.title || itemTitle } );
					} );
				}
			} catch ( err ) {
				results.errors.push( { title: itemTitle, message: err.message } );
			}
		}

		setImportProgress( { completed: total, total, currentItem: '' } );
		setImportResults( results );
		setImporting( false );
	};

	/**
	 * Cancel an in-progress import.
	 */
	const handleCancelImport = () => {
		cancelledRef.current = true;
	};

	/**
	 * Close the import modal and clear results.
	 */
	const closeImportModal = () => {
		setImporting( false );
		setImportResults( null );
		setError( '' );
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
								<span className="notion2wp-import__title-archived">
									({ __( 'Archived', 'notion2wp' ) })
								</span>
							) }
							{ item.type === 'database' && item.description && (
								<div className="notion2wp-import__title-description">
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
					<img src={ item.media } alt={ __( 'Media', 'notion2wp' ) } className="notion2wp-import__media-thumb" />
				) : (
					<img src={ placeholderImage } alt={ __( 'Site Logo', 'notion2wp' ) } className="notion2wp-import__media-thumb" />
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

	const { data: processedData, paginationInfo } = useMemo( () => {
		return filterSortAndPaginate( items, view, fields );
		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [ view ] );

	return (
		<div className="notion2wp-import">
			<Flex justify="space-between" align="flex-start" className="notion2wp-import__header">
				<FlexBlock>
					<p className="notion2wp-import__description">
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

			{ /* Fetch Error */ }
			{ error && ! importing && (
				<div className="notion2wp-import__error">
					{ error }
				</div>
			) }

			<ImportModal
				importing={ importing }
				importResults={ importResults }
				importProgress={ importProgress }
				onClose={ closeImportModal }
				onCancel={ handleCancelImport }
			/>

			{ /* Main Content */ }
			{ loading ? (
				<Card>
					<CardBody>
						<Flex align="center" justify="center" className="notion2wp-import__loading">
							<Spinner />
							<span className="notion2wp-import__loading-text">
								{ __( 'Loading items from Notion...', 'notion2wp' ) }
							</span>
						</Flex>
					</CardBody>
				</Card>
			) : items.length === 0 ? (
				<Card>
					<CardBody>
						<div className="notion2wp-import__empty">
							<h3>{ __( 'No Items Found', 'notion2wp' ) }</h3>
							<p>
								{ __( 'No pages, databases or data sources found in your Notion workspace.', 'notion2wp' ) }
								<br />
								{ __( 'Make sure you\'re connected and have shared pages with your integration.', 'notion2wp' ) }
								<br />
								{ __( 'Switch to the settings tab above to establish a connection.', 'notion2wp' ) }
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
