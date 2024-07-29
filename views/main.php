<form method="post">

  <?php do_action('ctf_success_message', $atts['form_success_message']); ?>

	<?php if ($messages = $this->messages->get_error_messages()) : ?>
    <p style="border: solid 1px #dd98a9;
    border-radius: 3px;
    padding: 12px;
    background-color: #fff2f0;">
		<?php echo implode('<br>', $messages); ?>
    </p>
	<?php endif; ?>

  <p>
    <label for="np-ctf-panel-email">
		<?php echo $atts['form_email_label']; ?>:
      <input id="np-ctf-panel-email" class="form-control form-control-lg" type="email" name="email" placeholder="youremail@company.com">
    </label>
  </p>

  <p>
    <label for="np-ctf-panel-license-key">
		<?php echo $atts['form_license_label']; ?>:
      <input id="np-ctf-panel-license-key" class="form-control form-control-lg" type="text" name="license_key" placeholder="XXXXXXXX-XXXXXXXX-XXXXXXXX-XXXXXXXX">
    </label>
  </p>

  <p>
    <label for="np-ctf-panel-product">
		<?php echo $atts['form_product_label']; ?>:
      <select id="np-ctf-panel-product" class="form-control form-control-lg" name="product" required>
        <option value=""><?php echo $atts['form_product_label']; ?></option>
        <?php foreach ( $atts['form_products'] as $slug => $product ) : ?>
          <option value="<?php echo $slug; ?>"><?php echo $product; ?></option>
        <?php endforeach; ?>
      </select>
    </label>
  </p>

  <p>
    <button class="button button-primary btn btn-primary" type="submit">
		<?php echo $atts['form_button_label']; ?>
    </button>
  </p>

  <input type="hidden" name="action" value="np-ctf"> 

	<?php wp_nonce_field('np-ctf-nonce'); ?>

</form>
