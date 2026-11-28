<?php
/** Import review signal containing only already authorized metadata.
 *
 * @package FileWarden
 */

namespace FileWarden;

/** Not a persistence failure: the upload has not been stored. */
final class DuplicateContent extends \UnexpectedValueException {
	/** Authorized review.
	 *
	 * @var array
	 */
	public $review;

	/** Initialize the authorized review signal.
	 *
	 * @param array $review Authorized review.
	 */
	public function __construct( array $review ) {
		parent::__construct( 'Identical content requires confirmation.' );
		$this->review = $review;
	}
}
