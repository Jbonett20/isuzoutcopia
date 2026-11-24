const myModal = new bootstrap.Modal(document.getElementById("modalAdd"));

$(function () {

  $("#roles").select2({
    placeholder: "Select one or more roles",
    allowClear: false,
    closeOnSelect: false,
    dropdownParent: $("#modalAdd"),
    templateResult: function (state) {
      if (!state.id) {
        return state.text;
      }
      if (state.element && state.element.selected) {
        return null;
      }
      return state.text;
    }
  });

  window.refreshRoleOptions = function () {
    const selected = $("#roles").val() || [];

    $("#roles option").each(function () {
      const value = $(this).val();
      if (!value) {
        return;
      }
      $(this).prop('hidden', selected.includes(value));
    });

    const select2Data = $("#roles").data('select2');
    if (select2Data && select2Data.$results) {
      select2Data.$results.find('li[aria-selected="true"]').hide();
      select2Data.$results.find('li[aria-selected="false"]').show();
    }
  };

  $("#roles").on("select2:select select2:unselect", function () {
    refreshRoleOptions();
  });

  $("#roles").on('select2:open', function () {
    refreshRoleOptions();
  });

  refreshRoleOptions();

  $("#dealer").select2({
    placeholder: "Select an option",
    templateResult: formatState,
    templateSelection: formatSelection,
  });

  $("#estado").select2({
    placeholder: "Select an option",
  });

  // Formato personalizado para select con banderas
  function formatState(state) {
    if (!state.id) return state.text;
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

  // Cargar datos iniciales
  CargarData();
});

// ✅ Declaramos CargarData en global para que se pueda llamar desde cualquier parte
function CargarData() {
  $("#TabUsersItems").load(
    "listdetailusers.php",
    function (response, status, xhr) {
      if (status === "success") {
        console.log("Contenido cargado exitosamente");
        DataTables();
      } else {
        console.log("Error al cargar:", xhr.status, xhr.statusText);
      }
    }
  );
}

// Inicialización de DataTable con columnas ordenables y no ordenables
function DataTables() {
  var unsortableColumns = [];
  $("#TableListUsers")
    .find("thead th")
    .each(function () {
      if ($(this).hasClass("no-sort")) {
        unsortableColumns.push({ bSortable: false });
      } else {
        unsortableColumns.push({ bSortable: true });
      }
    });

  $("#TableListUsers").dataTable({
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
  document.getElementById("frmReg").reset();
  myModal.show();

  if (document.getElementById("estado")) {
    document.getElementById("contentEstado").style.display = "none";
    if ($("#estado").hasClass("select2-hidden-accessible")) {
      $("#estado").select2("destroy");
    }
    document.getElementById("estado").removeAttribute("required");
    document.getElementById("estado").value = "";
  }

  document.getElementById("password-field").value = "";
  document.getElementById("password-field").setAttribute("required", "required");

  // Clear multiple roles select
  $("#roles").val(null).trigger("change");
  if (typeof refreshRoleOptions === 'function') {
    refreshRoleOptions();
  }
  
  document.getElementById("registroid").value = "";
}

function EditRegistro(el) {
  myModal.show();
  const item = JSON.parse(el.getAttribute("data-item"));

  document.getElementById("registroid").value = item.usuarioid;
  document.getElementById("names").value = item.nombres;
  document.getElementById("email").value = item.email;
  document.getElementById("cargodealer").value = item.cargodealer;
  document.getElementById("dealer").value = item.dealerid;

  // Cargar roles del usuario
  if (item.roles && Array.isArray(item.roles) && item.roles.length > 0) {
    const rolesIds = item.roles.map(role => role.rolid);

    $("#roles").val(rolesIds).trigger("change");
    if (typeof refreshRoleOptions === 'function') {
      refreshRoleOptions();
    }
  } else {
    // Fallback: fetch roles from server if not in JSON
    fetch('../controllers/users.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded'
      },
      body: 'opc=obtenerRoles&usuarioid=' + item.usuarioid
    })
    .then(response => response.json())
    .then(data => {
      if (data.success && data.roles) {
        $("#roles").val(data.roles).trigger("change");
        if (typeof refreshRoleOptions === 'function') {
          refreshRoleOptions();
        }
      }
    })
    .catch(error => console.error('Error:', error));
  }

  if (document.getElementById("estado")) {
    document.getElementById("contentEstado").style.display = "flex";
    document.getElementById("estado").value = item.estadoid;

    if (!$("#estado").hasClass("select2-hidden-accessible")) {
      $("#estado").select2({
        placeholder: "Select an option",
      });
    }
    document.getElementById("estado").setAttribute("required", "required");
    $("#estado").val(item.estadoid).trigger("change");
  }
  document.getElementById("password-field").value = "";
  document.getElementById("password-field").removeAttribute("required");

  $("#dealer").val(item.dealerid).trigger("change");
}

function toggleDealerSelect() {
  const dealerSelect = document.getElementById("dealer");
  const rolesSelect = document.getElementById("roles");
  const selectedRoles = $(rolesSelect).val() || [];
  
  // Check if any of the selected roles is admin (1 or 4)
  const isAdmin = selectedRoles.some(role => role === "1" || role === "4");
  
  dealerSelect.disabled = isAdmin;
  if (isAdmin) {
    dealerSelect.value = "";
    dealerSelect.removeAttribute("required");
  } else {
    dealerSelect.setAttribute("required", "required");
  }

  if ($(dealerSelect).hasClass("select2-hidden-accessible")) {
    $(dealerSelect).val(dealerSelect.value).trigger("change");
  }
}

$("#roles").on("change", function () {
  toggleDealerSelect();
});

document
  .getElementById("togglePassword")
  .addEventListener("click", function () {
    const passwordInput = document.getElementById("password-field");
    const icon = document.getElementById("toggleIcon");

    if (passwordInput.type === "password") {
      passwordInput.type = "text";
      icon.classList.remove("fa-eye");
      icon.classList.add("fa-eye-slash");
    } else {
      passwordInput.type = "password";
      icon.classList.remove("fa-eye-slash");
      icon.classList.add("fa-eye");
    }
  });

document
  .getElementById("frmReg")
  .addEventListener("submit", async function (e) {
    e.preventDefault();

    const form = e.target;
    const formData = new FormData(form);

    const registroid = document.getElementById("registroid");

    if (registroid.value === "") {
      formData.delete("estado");
      $("#estado").prop("required", false);
    }

    formData.append("opc", registroid.value === "" ? "crear" : "editar");

    if (!form.checkValidity()) {
      form.reportValidity();
      return;
    }

    if (formData.get("opc") === "crear") {
      formData.delete("registroid");
    }

    try {
      const response = await fetch("../controllers/users.php", {
        method: "POST",
        body: formData,
      });

      if (!response.ok)
        throw new Error(
          "The data must contain errors, otherwise it cannot be processed.!"
        );

      const data = await response.json();

      if (!data.success) {
        throw new Error(
          data.message ||
            "The data must contain errors, otherwise it cannot be processed.!"
        );
      }

      myModal.hide();
      document.getElementById("frmReg").reset();

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
