<?php

return [
    /*
     | Page-length choices shown in Customize Table. Yajra still paginates
     | server-side; this only caps how many rows a user may request per page.
     */
    'page_lengths' => [10, 25, 50, 100],

    'default_page_length' => 10,

    /*
     | Hard cap on export rows so the top Export button never loads an unbounded
     | result set into memory. Raise only after reviewing dataset size.
     */
    'export_row_limit' => 50000,

    'export_chunk_size' => 500,

    /*
     | Default page-number placement when Business Settings has not been
     | saved yet. Live value is business_settings.datatable_pagination_position
     | (bottom | top | both), applied via window.erpDtLayout().
     */
    'pagination_position' => 'bottom',
];
