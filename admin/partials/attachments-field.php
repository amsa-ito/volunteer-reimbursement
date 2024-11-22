<?php

$uploaded_files = [];
foreach($attachments as $attachement_id => $file_url){
    $attachment = get_attached_file( $attachement_id );
    if($attachment){
        $attachment_title = basename ( $attachment );
        $file_size = filesize( $attachment );
        
        $uploaded_files[$attachement_id] = [
            'name' => $attachment_title,
            'url' => $file_url,
            'size' => $file_size
        ];
    }

}


?>
<label for="vr-multiple-file-input">Please attach legible scans or photos of each original invoice and receipt.<span class="required">*</span></label>

<input type="file" id="vr-multiple-file-input" name="attachments[]" accept="image/*,.pdf" multiple>
<!-- List of uploaded files -->
<ul id="vr-file-list">

</ul>

<script type="text/javascript">
// Pass PHP $attachments array to JavaScript as initial uploadedFiles
    let uploadedFiles = <?php echo json_encode($uploaded_files); ?> || [];
</script>
<?php