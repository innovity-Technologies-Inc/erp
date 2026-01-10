$(document).ready(function () {
    "use strict";

    var csrf_test_name = $('#CSRF_TOKEN').val();
    var base_url = $('#base_url').val();
    var currency = $("#currency").val();

    console.log("🚀 Initializing Purchase Report DataTable...");

    var report = $('#reportlist').DataTable({
        responsive: true,
        processing: true,
        serverSide: true,
        aaSorting: [[1, "asc"]],
        columnDefs: [{
            bSortable: false,
            aTargets: [0, 2, 3, 4, 5, 6, 7]
        }],
        lengthMenu: [[10, 25, 50, 100, 250, 500], [10, 25, 50, 100, 250, 500]],
        dom: "'<'col-sm-4'l><'col-sm-4 text-center'><'col-sm-4'>Bfrtip",
        buttons: [
            { extend: "copy", exportOptions: { columns: ':visible' }, className: "btn-sm prints" },
            { extend: "csv", title: "Purchase Report", exportOptions: { columns: ':visible' }, className: "btn-sm prints" },
            { extend: "excel", title: "Purchase Report", exportOptions: { columns: ':visible' }, className: "btn-sm prints" },
            { extend: "pdf", title: "Purchase Report", exportOptions: { columns: ':visible' }, className: "btn-sm prints" },
            { extend: "print", title: "<center>Purchase Report</center>", exportOptions: { columns: ':visible' }, className: "btn-sm prints" }
        ],
        serverMethod: "POST",
        ajax: {
            url: base_url + "report/report/CheckReportList",
            type: "POST",
            data: function (data) {
                data.fromdate = $('#from_date').val();
                data.todate = $('#to_date').val();
                data.csrf_test_name = csrf_test_name;
                console.log("🔍 Sending Filter Dates:", data.fromdate, data.todate);
            },
            dataSrc: function (json) {
                console.log("📊 DataTables Received Data:", json);
                if (!json.aaData || json.aaData.length === 0) {
                    console.warn("⚠️ No data received for purchase report!");
                    return [];
                }
                return json.aaData;
            },
            error: function (xhr, error, thrown) {
                console.error("⚠️ DataTables Error:", xhr.responseText);
                alert("An error occurred while loading data. Check console for details.");
            }
        },
        columns: [
            { data: 'purchase_date', defaultContent: "N/A" },     // 0
            { data: 'purchase_id', defaultContent: "N/A" },        // 1
            { data: 'chalan_no', defaultContent: "N/A" },          // 2
            { data: 'supplier_name', defaultContent: "Unknown" },  // 3
            { data: 'total_amount', className: "text-right", defaultContent: "0.00" }, // 4
            { data: 'paid_amount', className: "text-right", defaultContent: "0.00" },  // 5
            { data: 'due_amount', className: "text-right", defaultContent: "0.00" },   // 6
            {
                data: 'payment_type',
                render: function (data) {
                    return data ? data : "<span style='color: red;'>Unknown Payment Method</span>";
                },
                defaultContent: "<span style='color: red;'>Unknown Payment Method</span>"
            } // 7
        ],
        footerCallback: function (row, data, start, end, display) {
            var api = this.api();

            function parseNumber(value) {
                if (typeof value === 'string') {
                    return parseFloat(value.replace(/,/g, '')) || 0;
                } else if (typeof value === 'number') {
                    return value;
                }
                return 0;
            }

            function getColumnTotal(index) {
                let total = api
                    .column(index, { page: 'current' })
                    .data()
                    .reduce(function (a, b) {
                        return parseNumber(a) + parseNumber(b);
                    }, 0);

                return currency + ' ' + total.toLocaleString(undefined, {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });
            }

            try {
                $(api.column(4).footer()).html(getColumnTotal(4)); // total_amount
                $(api.column(5).footer()).html(getColumnTotal(5)); // paid_amount
                $(api.column(6).footer()).html(getColumnTotal(6)); // due_amount
            } catch (e) {
                console.error("🚨 Error in footerCallback:", e);
            }
        }
    });

    $('#btn-filter').click(function () {
        console.log("🔄 Refreshing DataTable with Filters...");
        report.ajax.reload();
    });

    if (!$.fn.DataTable.isDataTable("#reportlist")) {
        console.warn("⚠️ DataTable not initialized correctly. Reinitializing...");
        report.destroy();
        $('#reportlist').DataTable();
    }
});