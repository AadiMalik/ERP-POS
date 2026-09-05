<script>
    $(function () {
        var {{$variable}} = "";
        initDataTable{{$variable}}();
        $.extend(true, $.fn.dataTable.defaults, {
            order: [[ 1, 'asc' ]],
            pageLength: 10,
            
        });
        @php
        if(!isset($variable)){
            $variable = "table";
        }
        @endphp
        @isset($start)
        $('.date_range').off('click').on('click' , function(){
            {{$variable}}.destroy()
            initDataTable{{$variable}}()

        })
        @endisset


        $('button[data-toggle="tab"]').off('shown.bs.tab').on('shown.bs.tab', function(e){
            {{$variable}}.destroy()
            initDataTable{{$variable}}()
        });
    })
    function initDataTable{{$variable}}(){
        {{$variable}}   = $("#{{$class}}").DataTable(
        {
            processing: true,
            pageLength: @if(isset($pageLength)) {{$pageLength}} @else 25  @endif,
            serverSide: true,
            @if(isset($buttons) && $buttons)

                dom: 'l Bfrtip',
            @endif
            @isset($notordering)
            "ordering": false,
            @endisset
            @isset($order)
            order: {!! $order !!},
            @endisset
            destroy : true,
            @php $__dtLocale = datatablesLocaleCode(); @endphp
            @if ($__dtLocale)
            language: {
                url: "//cdn.datatables.net/plug-ins/2.3.8/i18n/{{ $__dtLocale }}.json"
            },
            @endif
            ajax: {
                url: "{{$route}}",
                method: "POST",
                data: {
                    "_token": "{{csrf_token()}}",
                    "notification_filter": localStorage.getItem('notification_filter') != null ? 
                    localStorage.getItem('notification_filter'): null,
                    @isset($params)
                    {!!$params!!},
                    @endisset
                    @isset($datefilter)
                    "start_date": filterStartDate,
                    "end_date": filterEndDate,
                    @endisset
                }
            },
            @isset($footerCallback)
            {!!$footerCallback!!},
            @endisset

            @isset($createdRow)
            {!!$createdRow!!},
            @endisset

            @isset($rowCallback)
            {!!$rowCallback!!},
            @endisset

            @if(isset($buttons) && $buttons)
            buttons:[
            {
                extend: 'selectAll',
                className: 'btn-primary',
                text: '{{ __('common.select_all') }}',
                exportOptions: {
                    columns: ':visible'
                }
            },
            {
                extend: 'selectNone',
                className: 'btn-primary',
                text: '{{ __('common.deselect_all') }}',
                exportOptions: {
                    columns: ':visible'
                }
            },
            {
                extend: 'excel',
                text: "{{ __('common.download') }}",
                className: 'btn-info',
            },
          
            @isset($buttonCsv)
            {
                'text': 'Download CSV',
                'className': 'btn-success ',
                'action': function () {
                    var data = {{$variable}}.rows({selected: true}).data();
                    let ids = []
                    for (let i = 0; i < data.length; i++) {
                        ids.push(data[i].id);
                    }
                    $('#ids').val(ids)
                    $('#exportExcel').submit();
                }
            },
            @endisset
            
            
            
            
            ],
            @endif
            @isset($detail)
            columnDefs: [
                {
                    targets: 0,
                    orderable: false,
                    searchable: false,
                    className: 'dt-control text-center',
                    data: null,
                    defaultContent: '<i class="fa fa-chevron-down dt-control-icon" aria-hidden="true"></i>'
                }
            ],
            @endisset
            columns:[
            {!! $columns !!}
            ]
        }
        )
        if(localStorage.getItem('notification_filter') != null)
            localStorage.removeItem('notification_filter');

        @isset($detail)
        // Generic expandable detail row: the server pre-renders a hidden
        // `row_detail` HTML field on every row (not part of the visible
        // `columns` list) so no extra request is needed to expand it. Any
        // element inside a row with the `dt-control` class (the leading
        // toggle icon, or e.g. a "+N more" link inside a truncated column)
        // opens the same detail row - keeps the main row compact while
        // still surfacing all the data.
        $('#{{$class}} tbody').off('click.dtDetail').on('click.dtDetail', '.dt-control', function () {
            var tr = $(this).closest('tr');
            var row = {{$variable}}.row(tr);

            if (row.child.isShown()) {
                row.child.hide();
                tr.removeClass('shown');
            } else {
                row.child(row.data().row_detail || '<div class="dt-detail-empty text-muted">{{ __('common.no_additional_details') }}</div>', 'dt-detail-row').show();
                tr.addClass('shown');
            }
        });
        @endisset
    }
    </script>
    