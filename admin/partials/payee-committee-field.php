<?php
$saved_options = get_option('vr_committee_options', "");
$options = array_filter(array_map('trim', explode("\n", $saved_options))); // Split into array and trim spaces
	
// Determine if the value is in the predefined options
$isOther = !in_array($payee_committee, $options);


?>
<label for="payee_committee">Select Committee<span class="required">*</span></label>
<select id="payee_committee" name="payee_committee" required>
    <?php foreach ($options as $option): ?>
        <option value="<?php echo esc_attr($option); ?>" 
            <?php echo ($payee_committee === $option || ($isOther && $option === "Other")) ? 'selected' : ''; ?>>
            <?php echo esc_html($option); ?>
        </option>
    <?php endforeach; ?>
</select>

<input type="text" id="payee-other-committee" 
    name="payee_other_committee" 
    placeholder="Please specify the committee"
    style="display: <?php echo $isOther ? 'block' : 'none'; ?>; margin-top: 10px;"
    value="<?php echo $isOther ? esc_attr($payee_committee) : ''; ?>">
