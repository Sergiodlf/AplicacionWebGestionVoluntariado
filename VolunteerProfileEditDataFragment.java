package com.example.appgestionvoluntariado.Fragments.Volunteer;

import static com.example.appgestionvoluntariado.Utils.FormData.CAR_LIST;
import static com.example.appgestionvoluntariado.Utils.FormData.EXPERIENCE_LIST;
import static com.example.appgestionvoluntariado.Utils.FormData.LANGUAGE_LIST;

import android.app.DatePickerDialog;
import android.content.res.ColorStateList;
import android.graphics.Color;
import android.os.Bundle;
import android.util.Log;
import android.view.LayoutInflater;
import android.view.View;
import android.view.ViewGroup;
import android.widget.ArrayAdapter;
import android.widget.AutoCompleteTextView;
import android.widget.Button;
import android.widget.TextView;
import android.widget.Toast;

import androidx.annotation.NonNull;
import androidx.annotation.Nullable;
import androidx.fragment.app.Fragment;

import com.example.appgestionvoluntariado.Models.*;
import com.example.appgestionvoluntariado.R;
import com.example.appgestionvoluntariado.Services.APIClient;
import com.example.appgestionvoluntariado.Utils.CategoryManager;
import com.example.appgestionvoluntariado.Utils.StatusHelper;
import com.google.android.material.bottomsheet.BottomSheetDialog;
import com.google.android.material.button.MaterialButton;
import com.google.android.material.chip.Chip;
import com.google.android.material.chip.ChipGroup;
import com.google.android.material.textfield.TextInputEditText;
import com.google.gson.Gson;

import java.util.ArrayList;
import java.util.Calendar;
import java.util.HashMap;
import java.util.List;
import java.util.Locale;
import java.util.Map;

import retrofit2.Call;
import retrofit2.Callback;
import retrofit2.Response;

public class VolunteerProfileEditDataFragment extends Fragment {

    // UI Components
    private TextInputEditText etFullName, etDni, etBirthday, etEmail;
    private AutoCompleteTextView acLanguages, acExperience, acCycle, acCar, acZone, acDay, acTimeZone;
    private MaterialButton btnSave, btnSelectSkills, btnSelectInterests, btnSelectDisponibility, btnSelectLanguage;
    private ChipGroup cgSummary;
    private View loadingOverlay;

    // Servicios y Datos
    private APIClient.AuthAPIService authAPIService;
    private Volunteer currentVolunteer;

    // Listas Maestras
    private final List<Skill> masterSkillsList = new ArrayList<>();
    private final List<Interest> masterInterestsList = new ArrayList<>();
    private final List<Cycle> masterCyclesList = new ArrayList<>();
    private final List<String> cyclesNamesList = new ArrayList<>(); // Solo nombres para el Adapter

    // Selecciones (IDs para enviar al backend, Nombres para UI)
    private final List<Integer> selectedSkillIds = new ArrayList<>();
    private final List<Integer> selectedInterestIds = new ArrayList<>();
    private final List<String> selectedLanguages = new ArrayList<>();
    private final List<String> selectedAvailability = new ArrayList<>();

    @Nullable
    @Override
    public View onCreateView(@NonNull LayoutInflater inflater, @Nullable ViewGroup container, @Nullable Bundle savedInstanceState) {
        View view = inflater.inflate(R.layout.fragment_volunteer_edit_data, container, false);
        
        initViews(view);
        setupAdapters();
        setupListeners();
        
        loadMasterDataThenProfile(); // Carga en cadena: Categorias -> Ciclos -> Perfil
        
        return view;
    }

    private void initViews(View v) {
        // CORRECCIÓN: IDs coincidien con tu XML
        etFullName = v.findViewById(R.id.etName); // ID correcto del EditText
        etDni = v.findViewById(R.id.etEditDni);
        etEmail = v.findViewById(R.id.etEmail);
        etBirthday = v.findViewById(R.id.etEditBirthday);
        
        acZone = v.findViewById(R.id.actvZone);
        acLanguages = v.findViewById(R.id.actvIdiomas);
        acExperience = v.findViewById(R.id.etEditExperience);
        acCycle = v.findViewById(R.id.etEditCycle);
        acCar = v.findViewById(R.id.etEditCar);
        acDay = v.findViewById(R.id.actvDays);
        acTimeZone = v.findViewById(R.id.actvTimeSlots);

        btnSave = v.findViewById(R.id.btnSaveFullData);
        btnSelectSkills = v.findViewById(R.id.btnEditSelectSkills);
        btnSelectInterests = v.findViewById(R.id.btnEditSelectInterests);
        btnSelectDisponibility = v.findViewById(R.id.btnAddAvailability);
        btnSelectLanguage = v.findViewById(R.id.btnAddLanguage);

        cgSummary = v.findViewById(R.id.cgSummary);
        loadingOverlay = v.findViewById(R.id.loadingOverlay);

        authAPIService = APIClient.getAuthAPIService();
    }

    private void setupAdapters() {
        // Inicializar adapters vacíos o estáticos
        acCycle.setAdapter(new ArrayAdapter<>(requireContext(), android.R.layout.simple_dropdown_item_1line, cyclesNamesList));
        acExperience.setAdapter(new ArrayAdapter<>(requireContext(), android.R.layout.simple_dropdown_item_1line, EXPERIENCE_LIST));
        acLanguages.setAdapter(new ArrayAdapter<>(requireContext(), android.R.layout.simple_dropdown_item_1line, LANGUAGE_LIST));
        acCar.setAdapter(new ArrayAdapter<>(requireContext(), android.R.layout.simple_dropdown_item_1line, CAR_LIST));
        
        // ZONAS (Opcional, si no tienes lista estática puedes hardcodearla)
        String[] zonas = {"Pamplona", "Tudela", "Estella", "Tafalla", "Burlada", "Global"}; 
        acZone.setAdapter(new ArrayAdapter<>(requireContext(), android.R.layout.simple_dropdown_item_1line, zonas));
        
        // DIAS Y FRANJAS
        String[] dias = {"Lunes", "Martes", "Miércoles", "Jueves", "Viernes", "Sábado", "Domingo"};
        String[] franjas = {"Mañana", "Tarde", "Noche"};
        acDay.setAdapter(new ArrayAdapter<>(requireContext(), android.R.layout.simple_dropdown_item_1line, dias));
        acTimeZone.setAdapter(new ArrayAdapter<>(requireContext(), android.R.layout.simple_dropdown_item_1line, franjas));
    }

    private void setupListeners() {
        requireView().findViewById(R.id.topAppBar).setOnClickListener(v -> getParentFragmentManager().popBackStack());
        
        etBirthday.setOnClickListener(v -> showDatePicker());

        btnSave.setOnClickListener(v -> { if (validateForm()) saveData(); });

        btnSelectSkills.setOnClickListener(v -> openMultiSelectSheet("Habilidades", masterSkillsList, selectedSkillIds));
        btnSelectInterests.setOnClickListener(v -> openMultiSelectSheet("Intereses", masterInterestsList, selectedInterestIds));

        btnSelectLanguage.setOnClickListener(v -> {
            String val = acLanguages.getText().toString();
            if(!val.isEmpty() && !selectedLanguages.contains(val)) {
                selectedLanguages.add(val);
                renderAllChips();
                acLanguages.setText("");
            }
        });

        btnSelectDisponibility.setOnClickListener(v -> {
            String d = acDay.getText().toString();
            String f = acTimeZone.getText().toString();
            if(!d.isEmpty() && !f.isEmpty()) {
                String full = d + " " + f;
                if(!selectedAvailability.contains(full)) {
                   selectedAvailability.add(full);
                   renderAllChips();
                   acDay.setText(""); acTimeZone.setText("");
                }
            }
        });
    }

    // --- CARGA DE DATOS ---
    private void loadMasterDataThenProfile() {
        if (loadingOverlay != null) loadingOverlay.setVisibility(View.VISIBLE);

        // 1. Ciclos
        APIClient.getAdminService().getCiclos().enqueue(new Callback<List<Cycle>>() {
            @Override
            public void onResponse(Call<List<Cycle>> call, Response<List<Cycle>> response) {
                if(response.isSuccessful() && response.body() != null) {
                    masterCyclesList.clear();
                    masterCyclesList.addAll(response.body());
                    cyclesNamesList.clear();
                    for(Cycle c : masterCyclesList) cyclesNamesList.add(c.getFullCycle()); // "Nombre (Curso)"
                    // Refrescar adapter
                    ((ArrayAdapter)acCycle.getAdapter()).notifyDataSetChanged();
                }
                loadCategories();
            }
            @Override public void onFailure(Call<List<Cycle>> c, Throwable t) { loadCategories(); }
        });
    }

    private void loadCategories() {
        // 2. Habilidades e Intereses
        new CategoryManager().fetchAllCategories(
            new CategoryManager.CategoryCallback<Ods>() { @Override public void onSuccess(List<Ods> d) {} @Override public void onError(String e) {} },
            new CategoryManager.CategoryCallback<Skill>() {
                @Override public void onSuccess(List<Skill> d) { masterSkillsList.clear(); masterSkillsList.addAll(d); }
                @Override public void onError(String e) {}
            },
            new CategoryManager.CategoryCallback<Interest>() {
                @Override public void onSuccess(List<Interest> d) {
                    masterInterestsList.clear(); masterInterestsList.addAll(d);
                    fetchUserProfile(); // 3. Ir a Perfil
                }
                @Override public void onError(String e) { fetchUserProfile(); }
            },
            new CategoryManager.CategoryCallback<Need>() { @Override public void onSuccess(List<Need> d) {} @Override public void onError(String e) {} }
        );
    }

    private void fetchUserProfile() {
        authAPIService.getProfile().enqueue(new Callback<UserProfile<Volunteer>>() {
            @Override
            public void onResponse(Call<UserProfile<Volunteer>> call, Response<UserProfile<Volunteer>> response) {
                if (loadingOverlay != null) loadingOverlay.setVisibility(View.GONE);
                if (response.isSuccessful() && response.body() != null) {
                    currentVolunteer = response.body().getDatos();
                    fillFields(currentVolunteer);
                }
            }
            @Override public void onFailure(Call<UserProfile<Volunteer>> call, Throwable t) {
                if (loadingOverlay != null) loadingOverlay.setVisibility(View.GONE);
            }
        });
    }

    private void fillFields(Volunteer v) {
        // Nombre completo (juntar campos)
        String apellido2 = (v.getApellido2() != null) ? " " + v.getApellido2() : "";
        String full = v.getFirstName() + " " + v.getSurname() + apellido2;
        etFullName.setText(full.trim());
        
        etDni.setText(v.getDni());
        etEmail.setText(v.getCorreo());
        etBirthday.setText(v.getBirthDate());
        acZone.setText(v.getZone(), false);
        acExperience.setText(v.getExperience(), false);
        acCar.setText(v.getHasCar() ? "Si" : "No", false);
        
        // Ciclo (si viene del backend)
        if(v.getCycle() != null) acCycle.setText(v.getCycle(), false);

        // Listas
        selectedLanguages.clear();
        if(v.getLanguages() != null) selectedLanguages.addAll(v.getLanguages());
        
        selectedAvailability.clear();
        if(v.getDisponibilidad() != null) selectedAvailability.addAll(v.getDisponibilidad()); // Asumiendo getter

        selectedSkillIds.clear();
        if(v.getSkills() != null) {
             // Si el modelo Volunteer tiene objetos CategoryItem con ID
             for(Volunteer.CategoryItem item : v.getSkills()) selectedSkillIds.add(item.id);
        }

        selectedInterestIds.clear();
        if(v.getInterests() != null) {
             for(Volunteer.CategoryItem item : v.getInterests()) selectedInterestIds.add(item.id);
        }

        renderAllChips();
    }

    private void renderAllChips() {
        cgSummary.removeAllViews();
        
        // Helper interno
        addChipList(selectedLanguages, "#E1BEE7"); // Lila
        addChipList(selectedAvailability, "#FFF9C4"); // Amarillo
        
        // Habilidades (buscar nombre por ID)
        for(Integer id : selectedSkillIds) {
            for(Skill s : masterSkillsList) if(s.getId() == id) addChip(s.getName(), "#BBDEFB", id, "skill");
        }
        // Intereses
        for(Integer id : selectedInterestIds) {
            for(Interest i : masterInterestsList) if(i.getId() == id) addChip(i.getName(), "#C8E6C9", id, "interest");
        }
    }
    
    private void addChipList(List<String> items, String colorHex) {
        for(String s : items) {
            Chip chip = new Chip(requireContext());
            chip.setText(s);
            chip.setCloseIconVisible(true);
            chip.setChipBackgroundColor(ColorStateList.valueOf(Color.parseColor(colorHex)));
            chip.setOnCloseIconClickListener(v -> {
                items.remove(s);
                cgSummary.removeView(chip);
            });
            cgSummary.addView(chip);
        }
    }

    private void addChip(String text, String colorHex, int id, String type) {
        Chip chip = new Chip(requireContext());
        chip.setText(text);
        chip.setCloseIconVisible(true);
        chip.setChipBackgroundColor(ColorStateList.valueOf(Color.parseColor(colorHex)));
        chip.setOnCloseIconClickListener(v -> {
             if(type.equals("skill")) selectedSkillIds.remove(Integer.valueOf(id));
             else if(type.equals("interest")) selectedInterestIds.remove(Integer.valueOf(id));
             cgSummary.removeView(chip);
        });
        cgSummary.addView(chip);
    }

    // --- GUARDADO ---
    private void saveData() {
        if (loadingOverlay != null) loadingOverlay.setVisibility(View.VISIBLE);

        // 1. Split Nombre
        String[] parts = etFullName.getText().toString().trim().split(" ");
        String nombre = parts.length > 0 ? parts[0] : "";
        String ap1 = parts.length > 1 ? parts[1] : "";
        String ap2 = parts.length > 2 ? parts[2] : "";

        // 2. Mapa de Update
        Map<String, Object> update = new HashMap<>();
        update.put("nombre", nombre);
        update.put("apellido1", ap1);
        update.put("apellido2", ap2);
        update.put("zona", acZone.getText().toString());
        update.put("experiencia", acExperience.getText().toString());
        update.put("fechaNacimiento", etBirthday.getText().toString());
        update.put("coche", acCar.getText().toString()); // "Si" o "No"
        
        update.put("idiomas", selectedLanguages);
        update.put("disponibilidad", selectedAvailability);
        update.put("habilidades", selectedSkillIds);
        update.put("intereses", selectedInterestIds);
        
        // 3. Ciclo: Buscar el objeto completo si existe
        String cicloText = acCycle.getText().toString();
        Cycle selectedCycleObj = null;
        for(Cycle c : masterCyclesList) {
            if(c.getFullCycle().equals(cicloText)) { selectedCycleObj = c; break; }
        }
        
        if (selectedCycleObj != null) {
            update.put("ciclo", selectedCycleObj); // Objeto Completo
        } else {
            update.put("ciclo", cicloText); // Fallback String
        }

        authAPIService.updateProfile(update).enqueue(new Callback<Void>() {
            @Override
            public void onResponse(Call<Void> call, Response<Void> response) {
                if (loadingOverlay != null) loadingOverlay.setVisibility(View.GONE);
                if (response.isSuccessful()) {
                    Toast.makeText(getContext(), "Perfil actualizado", Toast.LENGTH_SHORT).show();
                    getParentFragmentManager().popBackStack();
                } else {
                    Toast.makeText(getContext(), "Error al guardar", Toast.LENGTH_SHORT).show();
                }
            }
            @Override public void onFailure(Call<Void> call, Throwable t) {
                if (loadingOverlay != null) loadingOverlay.setVisibility(View.GONE);
                Toast.makeText(getContext(), "Error de red", Toast.LENGTH_SHORT).show();
            }
        });
    }

    private boolean validateForm() {
        if (etFullName.getText().toString().isEmpty()) {
            StatusHelper.showStatus(getContext(), "Error", "El nombre es obligatorio", true);
            return false;
        }
        return true;
    }

    private void showDatePicker() {
        Calendar c = Calendar.getInstance();
        new DatePickerDialog(requireContext(), (view, y, m, d) -> {
            etBirthday.setText(String.format(Locale.getDefault(), "%d-%02d-%02d", y, m + 1, d));
        }, c.get(Calendar.YEAR), c.get(Calendar.MONTH), c.get(Calendar.DAY_OF_MONTH)).show();
    }

    // Sheet Genérico
    private <T> void openMultiSelectSheet(String title, List<T> data, List<Integer> selectedIds) {
        BottomSheetDialog dialog = new BottomSheetDialog(requireContext());
        View sheet = getLayoutInflater().inflate(R.layout.layout_selector_sheet, null); // Asegúrate que este layout existe o crea uno dinámico

        // ... Lógica de sheet simple o usar AlertDialog nativo si no tienes el layout ...
        // Simplificado con AlertDialog para no depender de otro XML:
        String[] names = new String[data.size()];
        boolean[] checked = new boolean[data.size()];
        
        for(int i=0; i<data.size(); i++) {
             if(data.get(i) instanceof Skill) names[i] = ((Skill)data.get(i)).getName();
             else names[i] = ((Interest)data.get(i)).getName();
             
             int id = (data.get(i) instanceof Skill) ? ((Skill)data.get(i)).getId() : ((Interest)data.get(i)).getId();
             checked[i] = selectedIds.contains(id);
        }
        
        new android.app.AlertDialog.Builder(requireContext())
           .setTitle(title)
           .setMultiChoiceItems(names, checked, (d, w, isChecked) -> {
               int id = (data.get(w) instanceof Skill) ? ((Skill)data.get(w)).getId() : ((Interest)data.get(w)).getId();
               if(isChecked) { if(!selectedIds.contains(id)) selectedIds.add(id); }
               else selectedIds.remove(Integer.valueOf(id));
           })
           .setPositiveButton("OK", (d, w) -> renderAllChips())
           .show();
    }
}
