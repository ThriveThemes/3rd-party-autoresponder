if ( typeof TVE !== 'undefined' ) {
	/* in order to add API Tag functionality, we must add the tag controls to the API controls template */
	TVE.add_action( 'tcb.lead_generation.api_settings_template', ( $template, apiKey, model ) => {

		if ( apiKey === 'clever-reach' ) {
			$template.append( TVE.tpl( 'lead-generation/apis/default-tag-controls' )( {api: model} ) );
		}
	} );

	TVE.add_filter( 'tcb.lead_generation.api_logo', ( logo, id ) => {
		if ( id === 'clever-reach' ) {
			logo = thrive_third_party_api_localized_data.api_logo;
		}

		return logo;
	} );

	/* When the API settings are saved, also save the selected tags */
	TVE.$( TVE.main ).on( 'tve-api-options-clever-reach.tcb', function ( event, params ) {
		params.api.setConfig( TVE.get_inputs_value( params.$container, '.tve-api-extra' ) );
	} );
}
