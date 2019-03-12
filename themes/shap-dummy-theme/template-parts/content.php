<?php
/**
 * Template part for displaying posts
 *
 * @link https://codex.wordpress.org/Template_Hierarchy
 *
 * @package Simplelin
 */

?>

<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
	<div class="post-inner-content">
		<header class="entry-header">

			<?php the_title( '<h2 class="entry-title"><a href="' . esc_url( get_permalink() ) . '" rel="bookmark">', '</a></h2>'); ?>

			<div class="entry-meta">
				<?php simplelin_entry_meta(); ?>
			</div><!-- .entry-meta -->

		</header><!-- .entry-header -->

		<?php if ( '' !== get_the_post_thumbnail() && ! is_single() ) : ?>
			<div class="post-thumbnail">
				<a href="<?php the_permalink(); ?>" title="<?php the_title_attribute(); ?>"><?php the_post_thumbnail( 'simplelin-featured' ); ?>
				</a>
			</div><!-- .post-thumbnail -->
		<?php endif; ?>

		<div class="entry-content">
			<?php the_excerpt(); ?>
			<a href="<?php the_permalink() ?>"><button class="read-more"><?php echo esc_html( 'Read More', 'simplelin' ) ?></button></a>
		</div><!-- .entry-content -->
	</div><!-- .post-inner-content -->
</article><!-- #post-## -->