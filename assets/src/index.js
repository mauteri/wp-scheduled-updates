/**
 * WordPress dependencies.
 */
import { __ } from '@wordpress/i18n';
import { registerPlugin } from '@wordpress/plugins';
import { Button, TextControl } from '@wordpress/components';
import { PluginDocumentSettingPanel } from '@wordpress/edit-post';
import { useState, useEffect } from '@wordpress/element';
import { compose } from '@wordpress/compose';
import { useSelect, useDispatch, select, subscribe, withSelect, withDispatch } from '@wordpress/data';

const ScheduledUpdatesPanel = () => {
	return (
		<PluginDocumentSettingPanel
			name="wsu-settings"
			title={ __( 'Scheduled Updates', 'wp-scheduled-updates' ) }
			initialOpen={ true }
			className="wsu-settings"
		>
			<Button isPrimary href={ `${ global.wp_scheduled_updates.admin_url }post-new.php?post_type=wsu-${ global.wp_scheduled_updates.post_type }&wp_scheduled_post=${ global.wp_scheduled_updates.current_post }` }>
				{ __( 'Schedule Update', 'wp-scheduled-updates' ) }
			</Button>
		</PluginDocumentSettingPanel>
	);
};

const WSUPostTypePanel = ( { postType, postMeta, setPostMeta } ) => {
	// const editPost = useDispatch( 'core/editor' ).editPost;
	const [ postUpdate, setPostUpdate ] = useState( postMeta.wsu_update_post_id );
	//
	// let postUpdateMeta = useSelect(
	// 	( select ) => select( 'core/editor' ).getEditedPostAttribute( 'meta' ).wsu_update_post_id,
	// );

	// useEffect( () => {
	// 	if ( postMeta.wsu_update_post_id ) {
	// 		setPostUpdate( postMeta.wsu_update_post_id );
		// } else {
		// 	setPostUpdate( global.wp_scheduled_updates.update_post_id );
	// 	}
	// } );

	// useEffect( () => {
	// 	subscribe(() => {
	// 		const isSavingPost = select('core/editor').isSavingPost();
	//
	// 		if ( isSavingPost ) {
	// 			setPostMeta( { wsu_update_post_id: postUpdate } );
	// 		}
	// 	});
	// }, []);

	const updatePost = ( value ) => {
		setPostUpdate( value );
		setPostMeta( { wsu_update_post_id: value } );
		// editPost( { meta: { wsu_update_post_id: value } } );
	}

	return (
		<PluginDocumentSettingPanel
			name="wsu-panel"
			title={ __( 'Scheduled Updates', 'wp-scheduled-updates' ) }
			initialOpen={ true }
			className="wsu-panel"
		>
			<TextControl
				id="testme"
				label={ __( 'Post to update ' ) }
				value={ postUpdate }
				onChange={ ( value ) => updatePost( value ) }
			/>
		</PluginDocumentSettingPanel>
	);
};
const WSUPostTypePanelCompose = compose( [
	withSelect( ( select ) => {
		return {
			postMeta: select( 'core/editor' ).getEditedPostAttribute( 'meta' ),
			postType: select( 'core/editor' ).getCurrentPostType(),
		};
	} ),
	withDispatch( ( dispatch ) => {
		return {
			setPostMeta( newMeta ) {
				dispatch( 'core/editor' ).editPost( { meta: newMeta } );
			}
		};
	} )
] )( WSUPostTypePanel );

if ( '1' === global.wp_scheduled_updates.wsc_post_type ) {
	registerPlugin( 'wsu-panel', {
		render: WSUPostTypePanelCompose,
		icon: '',
	} );
} else if ( '1' === global.wp_scheduled_updates.enabled_post_type ) {
	registerPlugin( 'wsu-settings', {
		render: ScheduledUpdatesPanel,
		icon: '',
	} );
}
