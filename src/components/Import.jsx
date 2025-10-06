import { useState, useEffect } from '@wordpress/element';
import { Button, Spinner, Notice, CheckboxControl } from '@wordpress/components';

const Import = () => {
	const apiUrl = window.notion2wpAdmin?.apiUrl || '/wp-json/notion2wp/v1/';
	const nonce = window.notion2wpAdmin?.nonce || '';

	const [ items, setItems ] = useState( [] );
	const [ selectedItems, setSelectedItems ] = useState( new Set() );
	const [ loading, setLoading ] = useState( false );
	const [ importing, setImporting ] = useState( false );
	const [ error, setError ] = useState( '' );
	const [ success, setSuccess ] = useState( '' );
	const [ importResults, setImportResults ] = useState( null );

	// Fetch available items.
	useEffect( () => {
		fetchItems();
		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [] );

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
				setError( data.message || 'Failed to fetch items from Notion.' );
			}
		} catch ( err ) {
			setError( `Error fetching items: ${ err.message }` );
		}

		setLoading( false );
	};

	const handleSelectItem = ( itemId ) => {
		const newSelected = new Set( selectedItems );
		if ( newSelected.has( itemId ) ) {
			newSelected.delete( itemId );
		} else {
			newSelected.add( itemId );
		}
		setSelectedItems( newSelected );
	};

	const handleSelectAll = () => {
		if ( selectedItems.size === items.length ) {
			setSelectedItems( new Set() );
		} else {
			setSelectedItems( new Set( items.map( ( item ) => item.id ) ) );
		}
	};

	const handleImport = async () => {
		if ( selectedItems.size === 0 ) {
			setError( 'Please select at least one item to import.' );
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
				setSuccess( data.message || 'Import completed successfully!' );
				setImportResults( data );
				setSelectedItems( new Set() );
			} else {
				setError( data.message || 'Import failed.' );
			}
		} catch ( err ) {
			setError( `Import error: ${ err.message }` );
		}

		setImporting( false );
	};

	const filterPages = items.filter( ( item ) => item.type === 'page' );
	const filterDatabases = items.filter( ( item ) => item.type === 'database' );

	return (
		<div style={ { maxWidth: 1200, margin: '2em auto' } }>
			<h1>Import from Notion</h1>
			<p>Select pages or databases to import into WordPress.</p>

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

			{ importResults && (
				<div style={ { marginTop: 20, padding: 15, background: '#f0f0f1', borderRadius: 4 } }>
					<h3>Import Results</h3>
					{ importResults.success && importResults.success.length > 0 && (
						<div>
							<h4 style={ { color: '#0a7e1e' } }>
								✓ Successfully Imported ({ importResults.success.length })
							</h4>
							<ul>
								{ importResults.success.map( ( result ) => (
									<li key={ result.page_id }>
										Notion Page ID: { result.page_id } → WordPress Post ID:{ ' ' }
										<a
											href={ `/wp-admin/post.php?post=${ result.post_id }&action=edit` }
											target="_blank"
											rel="noreferrer"
										>
											{ result.post_id }
										</a>
									</li>
								) ) }
							</ul>
						</div>
					) }

					{ importResults.errors && importResults.errors.length > 0 && (
						<div>
							<h4 style={ { color: '#cc1818' } }>✗ Failed ({ importResults.errors.length })</h4>
							<ul>
								{ importResults.errors.map( ( result, idx ) => (
									<li key={ idx }>
										{ result.page_id }: { result.message }
									</li>
								) ) }
							</ul>
						</div>
					) }
				</div>
			) }

			<div style={ { marginTop: 20, marginBottom: 20 } }>
				<Button
					variant="primary"
					onClick={ handleImport }
					disabled={ importing || selectedItems.size === 0 }
				>
					{ importing ? 'Importing...' : `Import Selected (${ selectedItems.size })` }
				</Button>
				<Button
					variant="secondary"
					onClick={ fetchItems }
					disabled={ loading }
					style={ { marginLeft: 10 } }
				>
					{ loading ? 'Refreshing...' : 'Refresh List' }
				</Button>
			</div>

			{ loading ? (
				<div style={ { textAlign: 'center', padding: 40 } }>
					<Spinner />
					<p>Loading items from Notion...</p>
				</div>
			) : (
				<>
					{ items.length === 0 ? (
						<div style={ { padding: 40, textAlign: 'center', background: '#f0f0f1', borderRadius: 4 } }>
							<p>No pages or databases found. Make sure you&apos;re connected to Notion.</p>
						</div>
					) : (
						<>
							{ /* Pages Section */ }
							{ filterPages.length > 0 && (
								<div style={ { marginBottom: 30 } }>
									<h2>Pages ({ filterPages.length })</h2>
									<div style={ { marginBottom: 10 } }>
										<Button
											variant="link"
											onClick={ handleSelectAll }
										>
											{ selectedItems.size === items.length
												? 'Deselect All'
												: 'Select All' }
										</Button>
									</div>
									<table
										className="wp-list-table widefat fixed striped"
										style={ { background: '#fff' } }
									>
										<thead>
											<tr>
												<th style={ { width: 40 } }>Select</th>
												<th>Title</th>
												<th>Last Edited</th>
												<th>Actions</th>
											</tr>
										</thead>
										<tbody>
											{ filterPages.map( ( item ) => (
												<tr key={ item.id }>
													<td>
														<CheckboxControl
															checked={ selectedItems.has( item.id ) }
															onChange={ () => handleSelectItem( item.id ) }
														/>
													</td>
													<td>
														<strong>{ item.title }</strong>
														{ item.archived && (
															<span
																style={ {
																	marginLeft: 8,
																	color: '#999',
																	fontSize: 12,
																} }
															>
																(Archived)
															</span>
														) }
													</td>
													<td>{ new Date( item.last_edited_time ).toLocaleString() }</td>
													<td>
														<a
															href={ item.url }
															target="_blank"
															rel="noreferrer"
															style={ { textDecoration: 'none' } }
														>
															View in Notion →
														</a>
													</td>
												</tr>
											) ) }
										</tbody>
									</table>
								</div>
							) }

							{ /* Databases Section */ }
							{ filterDatabases.length > 0 && (
								<div>
									<h2>Databases ({ filterDatabases.length })</h2>
									<table
										className="wp-list-table widefat fixed striped"
										style={ { background: '#fff' } }
									>
										<thead>
											<tr>
												<th style={ { width: 40 } }>Select</th>
												<th>Title</th>
												<th>Description</th>
												<th>Properties</th>
												<th>Actions</th>
											</tr>
										</thead>
										<tbody>
											{ filterDatabases.map( ( item ) => (
												<tr key={ item.id }>
													<td>
														<CheckboxControl
															checked={ selectedItems.has( item.id ) }
															onChange={ () => handleSelectItem( item.id ) }
														/>
													</td>
													<td>
														<strong>{ item.title }</strong>
													</td>
													<td>{ item.description || '-' }</td>
													<td>
														{ item.properties && item.properties.length > 0 ? (
															<small>
																{ item.properties.map( ( p ) => p.name ).join( ', ' ) }
															</small>
														) : (
															'-'
														) }
													</td>
													<td>
														<a
															href={ item.url }
															target="_blank"
															rel="noreferrer"
															style={ { textDecoration: 'none' } }
														>
															View in Notion →
														</a>
													</td>
												</tr>
											) ) }
										</tbody>
									</table>
								</div>
							) }
						</>
					) }
				</>
			) }
		</div>
	);
};

export default Import;
