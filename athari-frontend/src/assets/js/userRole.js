
var table = $('#example').DataTable( {
  aaSorting: [],
  pagingType: 'full_numbers',
  buttons: [
     {
      extend: 'excelHtml5',
      exportOptions: {
        columns: [ 0, 1, 2, 3, 4, 5, 6 ]
      }
     },
    {
      extend: 'pdfHtml5',
      exportOptions: {
        columns: [ 0, 1, 2, 3, 4, 5, 6 ]
      }
    }
  ],
  responsive: true,
  language: {
    "search": "Recherche : ",
    "lengthMenu": "Seulement : _MENU_ entrées/page",
    "info":  "Du _START_ au _END_ sur _TOTAL_ entrées",
    "paginate": {
        "first":      '<i class="fa-solid fa-angles-left fa-xs mb-3"></i>',
        "last":       '<i class="fa-solid fa-angles-right fa-xs mb-3"></i>',
        "next":       '<i class="fa-solid fa-angle-right fa-3xs mt-2"></i>',
        "previous":   '<i class="fa-solid fa-angle-left fa-2xs mt-2"></i>'
    },
    "emptyTable": "Pas d'user disponible",
    "infoFiltered": " - filtré sur _MAX_ enregistrements",
    "zeroRecords":    "Aucun résultat trouvé",
    "infoEmpty":      "Aucune réponse",
  },
  columnDefs: [
      {
        responsivePriority: 1,
        targets: 0
      },
      {
        responsivePriority: 2,
        targets: -1
      }
    ]
} );
 
table.buttons().container()
  .appendTo( '#example_wrapper .col-md-6:eq(0)' );

var search = $(".dataTables_filter input")
    .attr("placeholder", "Votre recherche")
    .css({
      width: "250px",
      display: "inline-block"
    });

