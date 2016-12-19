<?php
/**
 * All functions and classes related to flagging
 *
 * This file keep all function required by flagging system.
 *
 * @link https://anspress.io
 * @since 2.3.4
 *
 * @package AnsPress
 **/

/**
 * All flag methods.
 */
class AnsPress_Flag {
	/**
	 * Ajax callback to process post flag button
	 *
	 * @since 2.0.0
	 */
	public static function action_flag() {
		$post_id = (int) ap_sanitize_unslash( 'post_id', 'r' );

		if ( ! ap_verify_nonce( 'flag_' . $post_id ) || ! is_user_logged_in() ) {
			ap_ajax_json( 'something_wrong' );
		}

		$userid = get_current_user_id();
		$is_flagged = ap_is_user_flagged( $post_id );

		// Die if already flagged.
		if ( $is_flagged ) {
			ap_ajax_json( array(
				'success'  => false,
				'snackbar' => [ 'message' => __( 'You have already reported this post.', 'anspress-question-answer' ) ],
			) );
		}

		ap_add_flag( $post_id );
		$count = ap_update_flags_count( $post_id );

		ap_ajax_json( array(
			'success'  => true,
			'action'   => [ 'count' => $count, 'active' => true ],
			'snackbar' => [ 'message' => __( 'Thank you for reporting this post.', 'anspress-question-answer' ) ],
		) );
	}

}

/**
 * Add flag vote data to ap_votes table.
 *
 * @param integer $post_id     Post ID.
 * @param integer $user_id     User ID.
 * @return integer|boolean
 */
function ap_add_flag( $post_id, $user_id = false ) {

	if ( false === $user_id ) {
		$user_id = get_current_user_id();
	}

	return ap_vote_insert( $post_id, $user_id, 'flag' );
}

/**
 * Count flag votes.
 *
 * @param integer $post_id Post ID.
 * @return  integer
 * @since  4.0.0
 */
function ap_count_post_flags( $post_id ) {
	$rows = ap_count_votes( [ 'vote_post_id' => $post_id, 'vote_type' => 'flag' ] );

	if ( false !== $rows ) {
		return (int) $rows[0]->count;
	}

	return 0;
}

/**
 * Check if user already flagged a post.
 *
 * @param bool|integer $post Post.
 * @return bool
 */
function ap_is_user_flagged( $post = null ) {
	$_post = ap_get_post( $post );

	if ( is_user_logged_in() ) {
		return ap_is_user_voted( $_post->ID, 'flag' );
	}

	return false;
}

/**
 * Flag button html.
 *
 * @param mixed   $post Post.
 * @return string
 * @since 0.9
 */
function ap_flag_btn_args( $post = null ) {

	if ( ! is_user_logged_in() ) {
		return;
	}

	$_post = ap_get_post( $post );
	$flagged = ap_is_user_flagged( $_post );

	$title = ( ! $flagged) ? (__( 'Flag this post', 'anspress-question-answer' )) : (__( 'You have flagged this post', 'anspress-question-answer' ));

	return $actions['close'] = array(
		'cb'   => 'flag',
		'icon'   => 'apicon-check',
		'query'  => [ '__nonce' => wp_create_nonce( 'flag_' . $_post->ID ), 'post_id' => $_post->ID ],
		'label'  => __( 'Flag', 'anspress-question-answer' ),
		'title'  => $title,
		'count'  => $_post->flags,
		'active' => $flagged,
	);
}
