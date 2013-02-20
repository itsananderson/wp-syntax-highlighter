<?php $count = 0; ?>
<div class="code code-<?php echo $lang; ?>">
	<div class="lines">
		<table>
			<?php while ( $count++ <= $lines ) : ?>
				<tr><td class="line"><?php echo $count; ?></td></tr>
			<?php endwhile; ?>
		</table>
	</div>
	<div class="highlighted-code highlighted-code-$lang">
		<?php echo $highlighted; ?>
	</div>
</div>