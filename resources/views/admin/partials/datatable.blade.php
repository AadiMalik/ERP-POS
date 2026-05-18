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
        $('.date_range').on('click' , function(){
            {{$variable}}.destroy()
            initDataTable{{$variable}}()
            
        })
        @endisset
        
        
        $('button[data-toggle="tab"]').on('shown.bs.tab', function(e){
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
            destroy : true,
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
                    "start_date": $('#start_date').val(),
                    "end_date": $('#end_date').val(),
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
                text: '{{ trans('global.select_all') }}',
                exportOptions: {
                    columns: ':visible'
                }
            },
            {
                extend: 'selectNone',
                className: 'btn-primary',
                text: '{{ trans('global.deselect_all') }}',
                exportOptions: {
                    columns: ':visible'
                }
            },
            {
                extend: 'excel',
                text: "Download",
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
            // columnDefs: [ {
            //     orderable: false,
            //     className: 'select-checkbox',
            //     targets:   0
            // },
            // @isset($child)
            // {
            //     className: 'dt-control',
            //     orderable: false,
            //     data: null,
            //     defaultContent: '',
            //     targets: 1
            // }
            // @endisset
            // ],
            // select: {
            //     style:    'multi',
            //     selector: 'td:first-child'
            // },
            columns:[
            {!! $columns !!}
            ]
        }
        )
        if(localStorage.getItem('notification_filter') != null)
            localStorage.removeItem('notification_filter');
    }
    @isset($child)
    $('.{{$class}} tbody').on('click', 'td.dt-control', function () {
        var tr = $(this).closest('tr');
        var row = {{$variable}}.row(tr);
        
        if (row.child.isShown()) {
            // This row is already open - close it
            row.child.hide();
            tr.removeClass('shown');
        } else {
            // Open this row
            console.log(row)
            row.child(format(row.data())).show();
            tr.addClass('shown');
        }
    });
    function format ( rowData ) {
        html = ''
        let url = "{{route('datatable.damage-inventory.detail' , [':id',':ware'])}}";
        url = url.replace(':id' , rowData.order_id)
        url = url.replace(':ware' , rowData.warehouse_id)
        $.ajax({
            url: url,
            async:false,
            success:function(res){
                result = ''
                $.each(res , function(index , i){
                    result += `<tr>`
                        result += `<td>${i.product?i.product.product_name:'N/A'}</td>`
                        result += `<td>${i.damage_quantity}</td>`
                        result += `</tr>`
                    })
                    html += `<table>
                        <thead>
                            <tr>
                                <th>Product Name</th>
                                <th>Product Quantity</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${result}
                        </tbody>
                        
                    </table>`
                    
                }
            })
            return html;
        }
        @endisset
    </script>
    