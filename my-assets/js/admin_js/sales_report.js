$(document).ready(function () {
    "use strict";

    var csrf_test_name = $('#CSRF_TOKEN').val();
    var base_url = $('#base_url').val();
    var currency = $("#currency").val();

    console.log("🚀 Initializing Sales Report DataTable...");

    var report = $('#reportlist').DataTable({
        responsive: true,
        processing: true,
        serverSide: true,
    
        aaSorting: [[1, "desc"]],
        columnDefs: [{ bSortable: false, aTargets: [0, 2, 3, 4, 5, 6, 7, 8] }],
    
        lengthMenu: [
            [10, 25, 50, 100, 250, 500],
            [10, 25, 50, 100, 250, 500]
        ],
    
        dom: "'<'col-sm-4'l><'col-sm-4 text-center'><'col-sm-4'>Bfrtip",
    
        buttons: [
            { extend: "copy", className: "btn-sm prints", exportOptions: { columns: ':visible' } },
            { extend: "csv", className: "btn-sm prints", title: "Sales Report", exportOptions: { columns: ':visible' } },
            { extend: "excel", className: "btn-sm prints", title: "Sales Report", exportOptions: { columns: ':visible' } },
            { extend: "pdf", className: "btn-sm prints", title: "Sales Report", exportOptions: { columns: ':visible' } },
            { extend: "print", className: "btn-sm prints", title: "<center>Sales Report</center>", exportOptions: { columns: ':visible' } }
        ],

        ajax: {
            url: base_url + "report/report/getSalesReportList",
            type: "POST",
            data: function (data) {
                data.fromdate = $('#from_date').val();
                data.todate = $('#to_date').val();
                data.csrf_test_name = csrf_test_name;

                // ✅ Log search value for debugging
                console.log("🔎 Search Term Sent:", data.search?.value || "(empty)");
                console.log("📤 Full Data Sent:", data);
            },
            dataSrc: function (json) {
                console.log("📊 API Response:", json);
                if (!json.aaData || json.aaData.length === 0) {
                    console.warn("⚠️ No sales data found.");
                    return [];
                }
                return json.aaData;
            },
            error: function (xhr, error, thrown) {
                console.error("❌ DataTables Error:", xhr.responseText);
                alert("❌ Error loading sales data. Check console for details.");
            }
        },

        columns: [
            { data: 'date' },
            { data: 'invoice_id' },
            { data: 'customer_name' },
            { 
                data: 'total_amount', 
                className: "total_amount text-right", 
                render: $.fn.dataTable.render.number(',', '.', 2, currency)
            },
            { 
                data: 'total_discount', 
                className: "total_discount text-right", 
                render: $.fn.dataTable.render.number(',', '.', 2, currency)
            },
            { 
                data: 'payable_amount', 
                className: "payable_amount text-right", 
                render: $.fn.dataTable.render.number(',', '.', 2, currency)
            },
            { 
                data: 'paid_amount', 
                className: "paid_amount text-right", 
                render: $.fn.dataTable.render.number(',', '.', 2, currency)
            },
            { 
                data: 'due_amount', 
                className: "due_amount text-right", 
                render: $.fn.dataTable.render.number(',', '.', 2, currency)
            },
            { 
                data: 'payment_type',
                render: function (data) {
                    return data && data.trim() !== "" 
                        ? data 
                        : "<span style='color: red;'>Unknown Payment Method</span>";
                }
            }
        ],

        footerCallback: function (row, data, start, end, display) {
            var api = this.api();

            function sumColumn(className) {
                var columnData = api.column('.' + className, { page: 'current' }).data();
                console.log(`🔍 Summing column: ${className}`, columnData);

                if (!columnData || columnData.length === 0) {
                    return currency + " 0.00";
                }

                var total = columnData.reduce(function (a, b) {
                    var x = parseFloat(a) || 0;
                    var y = parseFloat(b) || 0;
                    return x + y;
                }, 0);

                return currency + ' ' + total.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            }

            try {
                $(api.column('.total_amount').footer()).html(sumColumn('total_amount'));
                $(api.column('.total_discount').footer()).html(sumColumn('total_discount'));
                $(api.column('.payable_amount').footer()).html(sumColumn('payable_amount'));
                $(api.column('.paid_amount').footer()).html(sumColumn('paid_amount'));
                $(api.column('.due_amount').footer()).html(sumColumn('due_amount'));
            } catch (e) {
                console.error("🚨 Error in footerCallback:", e);
            }
        }
    });

    // 🔁 Manual Filter Button Trigger
    $('#btn-filter').click(function () {
        console.log("🔄 Refreshing DataTable with Filters...");
        report.ajax.reload();
    });
});