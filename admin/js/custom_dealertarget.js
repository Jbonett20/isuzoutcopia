const modalElement = document.getElementById("modalAdd");
const myModal = modalElement ? new bootstrap.Modal(modalElement) : null;

$(function () {
  var elDealerFilter = document.getElementById("dealerFilter");
  var tieneFiltroSelect =
    elDealerFilter && elDealerFilter.tagName === "SELECT";
  var $dealerFilter = $("#dealerFilter");

  if (tieneFiltroSelect) {
    $dealerFilter.select2({
      placeholder: "Select an option",
      templateResult: formatState,
      templateSelection: formatSelection,
    });
  }

  if (document.getElementById("dealerid") && document.getElementById("dealerid").tagName === "SELECT") {
    $("#dealerid").select2({
      placeholder: "Select an option",
      templateResult: formatState,
      templateSelection: formatSelection,
    });
  }

  function formatState(state) {
    if (!state.id) return state.text;
    if (!state.title || state.title.trim() === "") return state.text;
    var baseUrl = "../admin/img/country";
    var $state = $(
      '<span><img src="' +
        baseUrl +
        "/" +
        state.title +
        '" style="width:20px;" /> ' +
        state.text +
        "</span>"
    );
    return $state;
  }

  function formatSelection(state) {
    if (!state.id) return state.text;
    if (!state.title || state.title.trim() === "") return state.text;
    var baseUrl = "../admin/img/country";
    var $state = $(
      '<span><img src="' +
        baseUrl +
        "/" +
        state.title +
        '" style="width:20px;" /> ' +
        state.text +
        "</span>"
    );
    $state.find("span").text(state.text);
    $state.find("img").attr("src", baseUrl + "/" + state.title);
    return $state;
  }

  if (tieneFiltroSelect) {
    $dealerFilter.on("change", function () {
      CargarData();
    });
  }

  CargarData();
});

function getDealerFilter() {
  const value = $("#dealerFilter").val();
  return value && value !== "" ? value : "0";
}

function CargarData() {
  const dealerid = getDealerFilter();

  $("#TabItems").load(
    "listdetaildealertarget.php?dealerid=" + encodeURIComponent(dealerid),
    function (response, status, xhr) {
      if (status === "success") {
        DataTables();
      } else {
        console.log("Error al cargar:", xhr.status, xhr.statusText);
      }
    }
  );
}

function DataTables() {
  if (!document.getElementById("TableListDealerTarget")) {
    return;
  }

  var unsortableColumns = [];
  $("#TableListDealerTarget")
    .find("thead th")
    .each(function () {
      if ($(this).hasClass("no-sort")) {
        unsortableColumns.push({ bSortable: false });
      } else {
        unsortableColumns.push({ bSortable: true });
      }
    });

  $("#TableListDealerTarget").dataTable({
    sDom: "<'row'<'col-lg-6 hidden-sm-down'l><'col-lg-6'f>r>t<'row'<'col-lg-6'i><'col-lg-6'p>>",
    oLanguage: {
      sLengthMenu: "_MENU_",
      sInfo: "Showing <strong>_START_ to _END_</strong> of _TOTAL_ entries",
    },
    oClasses: {
      sFilter: "float-right",
      sFilterInput: "form-control input-rounded ml-sm",
    },
    aoColumns: unsortableColumns,
  });

  $("div.dataTables_length select").addClass("form-control");
}

function NewRegistro() {
  if (!myModal) return;

  document.getElementById("frmReg").reset();
  var elDealer = document.getElementById("dealerid");
  if (elDealer && elDealer.tagName === "SELECT") {
    $("#dealerid").val("").trigger("change");
  }
  document.getElementById("registroid").value = "";
  myModal.show();
}

function EditRegistro(el) {
  if (!myModal) return;

  const item = JSON.parse(el.getAttribute("data-item"));

  document.getElementById("registroid").value = item.dealertargetid;
  document.getElementById("year").value = item.year;
  document.getElementById("objetivo").value = item.objetivo;
  document.getElementById("objetivovlr").value =
    item.objetivovlr !== undefined && item.objetivovlr !== null
      ? item.objetivovlr
      : "";
  var elDealer = document.getElementById("dealerid");
  if (elDealer && elDealer.tagName === "SELECT") {
    $("#dealerid").val(item.dealerid).trigger("change");
  } else if (elDealer) {
    elDealer.value = item.dealerid;
  }

  myModal.show();
}

const frmReg = document.getElementById("frmReg");
if (frmReg) {
  frmReg.addEventListener("submit", async function (e) {
    e.preventDefault();

    const formData = new FormData(frmReg);
    const registroid = document.getElementById("registroid").value;

    formData.append("opc", registroid === "" ? "crear" : "editar");

    if (!frmReg.checkValidity()) {
      frmReg.reportValidity();
      return;
    }

    if (formData.get("opc") === "crear") {
      formData.delete("registroid");
    }

    try {
      const response = await fetch("../controllers/dealertarget.php", {
        method: "POST",
        body: formData,
      });

      if (!response.ok) {
        throw new Error("Unable to process your request.");
      }

      const data = await response.json();
      if (!data.success) {
        throw new Error(data.message || "Unable to process your request.");
      }

      myModal.hide();
      frmReg.reset();
      var elDealer = document.getElementById("dealerid");
      if (elDealer && elDealer.tagName === "SELECT") {
        $("#dealerid").val("").trigger("change");
      }

      Messenger().post({
        message: data.message,
        type: "success",
        showCloseButton: true,
      });

      CargarData();
    } catch (error) {
      Messenger().post({
        message: error.message,
        type: "error",
        showCloseButton: true,
      });
    }
  });
}
