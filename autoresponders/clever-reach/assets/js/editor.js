/**
 * This contains functionality that allows the tags and form settings to show in the Thrive Architect editor in the Lead Generation API settings.
 */
if ( typeof TVE !== 'undefined' ) {
	/* if you re-use this for another API, everything should still work :) */
	const CLEVER_REACH_API_KEY = thrive_third_party_api_localized_data.api_key,
		$mainEditorContainer = TVE.$( TVE.main );

	/**
	 * In order to add the API Tag functionality, we must add the tag controls to the API controls template.
	 * Same thing for adding the Forms selector.
	 * @param {jQuery} $template
	 * @param {String} apiKey
	 * @param {Object} model
	 */
	TVE.add_action( 'tcb.lead_generation.api_settings_template', ( $template, apiKey, model ) => {
		if ( apiKey === CLEVER_REACH_API_KEY ) {
			/* Functionality for forms */
			$template.append( TVE.tpl( 'lead-generation/apis/default-form-controls' )( {api: model} ) );
			/* Functionality for tags */
			$template.append( TVE.tpl( 'lead-generation/apis/default-tag-controls' )( {api: model} ) );
		}
	} );

	/**
	 * Functionality for forms
	 * Each time the API panel is re-rendered, set up a listener on the change event of the mailing list select so we can update the form list accordingly.
	 * @param {Event} event
	 * @param {Object} params
	 */
	$mainEditorContainer.on( `tve-api-after-render-${CLEVER_REACH_API_KEY}.tcb`, ( event, params ) => {
		const $container = params.$container,
			$listSelect = $container.find( '.api-list' ),
			$formSelect = $container.find( '.tcb-form-list' ),
			api = params.api,
			/* the form list is sent from the backend */
			forms = api.extra_settings.forms,
			/**
			 * @param {Event} listEvent
			 */
			onListChange = listEvent => {
				const selectedValue = listEvent ? listEvent.target.value : $listSelect.val();
				let hasForms = false;

				$formSelect.empty();

				if ( forms && forms[ selectedValue ] ) {
					const selectedForm = api.getConfig( 'form' ) || '';

					/* Add the forms to the forms select  */
					_.each( forms[ selectedValue ], form => {
						$formSelect.append( new Option( form.name, form.id, true, selectedForm === form.id ) );
					} );

					hasForms = true;
				}

				/* the forms select is only displayed if we have something to populate it with */
				$formSelect.toggle( hasForms );
				/* if no forms were found, display the 'No forms available' error, otherwise hide the error text */
				$container.find( '.tcb-forms-error' ).toggle( ! hasForms );
			};

		$listSelect.on( 'change', onListChange );

		onListChange();
	} );

	/**
	 * Functionality for tags and forms.
	 * When the API settings are saved, also save the selected tags and the selected form.
	 */
	$mainEditorContainer.on( `tve-api-options-${CLEVER_REACH_API_KEY}.tcb`, ( event, params ) => {
		/* 'get_inputs_value' is a Thrive Architect function that reads the selected values */
		params.api.setConfig( TVE.get_inputs_value( params.$container, '.tve-api-extra' ) );
	} );

	/**
	 * Adds the clever-reach logo in the Thrive Architect API list
	 * @param {String} logo
	 * @param {String} apiKey
	 * @returns {String} logo
	 */
	TVE.add_filter( 'tcb.lead_generation.api_logo', ( logo, apiKey ) => {
		if ( apiKey === CLEVER_REACH_API_KEY ) {
			logo = thrive_third_party_api_localized_data.api_logo;
		}

		return logo;
	} );
}
