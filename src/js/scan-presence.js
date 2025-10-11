import React, { useEffect, useState } from "react";
import {
  createUser,
  updateUser, // ✅ AJOUT : Import pour modification
  getUserById, // ✅ AJOUT : Import pour récupérer un utilisateur par ID
  updateMedecin,
  listServices,
  listUsers,
  listMedecins,
} from "../../../api/adminApi";
import Swal from "sweetalert2";

export default function AjoutMedecin() {
  // ✅ AJOUT : Détection du mode modification
  const [editMode, setEditMode] = useState(false);
  const [editUserId, setEditUserId] = useState(null);
  const [isLoading, setIsLoading] = useState(false);
  
  const [role, setRole] = useState("accueil");
  const [services, setServices] = useState([]);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [errors, setErrors] = useState({});
  const [touched, setTouched] = useState({});
  const [usernameChecking, setUsernameChecking] = useState(false);
  const [emailChecking, setEmailChecking] = useState(false);
  const [phoneChecking, setPhoneChecking] = useState(false);

  const [base, setBase] = useState({
    username: "",
    email: "",
    first_name: "",
    last_name: "",
    telephone: "",
    password: "",
    photo: null,
  });

  const [medecin, setMedecin] = useState({
    specialite: "",
    numero_ordre: "",
    service: "",
  });

  // ✅ CORRECTION : Fonction pour charger les données utilisateur à modifier
  const loadUserData = async (userId) => {
    try {
      setIsLoading(true);
      console.log("Chargement des données utilisateur ID:", userId);
      
      // ✅ CORRECTION : Utiliser getUserById au lieu de listUsers
      const user = await getUserById(userId);
      
      console.log("Utilisateur trouvé:", user);

      // Préremplir les données de base
      setBase({
        username: user.username || "",
        email: user.email || "",
        first_name: user.first_name || "",
        last_name: user.last_name || "",
        telephone: user.telephone || "",
        password: "", // ✅ Mot de passe vide en mode modification
        photo: null, // ✅ Photo actuelle non modifiable directement
      });

      setRole(user.role || "accueil");

      // ✅ Si c'est un médecin, charger ses données spécifiques
      if (user.role === "medecin") {
        try {
          const medecins = await listMedecins();
          const medecinData = medecins.find(m => m.utilisateur?.id === user.id);
          
          if (medecinData) {
            console.log("Données médecin trouvées:", medecinData);
            setMedecin({
              specialite: medecinData.specialite || "",
              numero_ordre: medecinData.numero_ordre || "",
              service: medecinData.service || "",
            });
          }
        } catch (err) {
          console.warn("Erreur lors du chargement des données médecin:", err);
        }
      }
    } catch (err) {
      console.error("Erreur lors du chargement de l'utilisateur:", err);
      await Swal.fire({
        title: "Erreur",
        text: "Impossible de charger les données de l'utilisateur",
        icon: "error",
      });
      // ✅ Rediriger vers la liste en cas d'erreur
      window.location.href = "/utilisateurs/liste";
    } finally {
      setIsLoading(false);
    }
  };

  // ✅ AJOUT : Détecter le mode modification au montage du composant
  useEffect(() => {
    const urlParams = new URLSearchParams(window.location.search);
    const editId = urlParams.get('edit');
    
    if (editId) {
      console.log("Mode modification détecté, ID:", editId);
      setEditMode(true);
      setEditUserId(editId);
      loadUserData(editId);
    }
  }, []);

  useEffect(() => {
    const loadServices = async () => {
      try {
        const data = await listServices();
        setServices(Array.isArray(data) ? data : []);
      } catch (_) {}
    };
    loadServices();
  }, []);

  const usernameRegex = /^[a-zA-Z][a-zA-Z0-9._]{2,19}$/;
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  const phoneRegex = /^(06|05|04)[0-9]{7}$/;

  const validateField = (name, value) => {
    let message = "";
    switch (name) {
      case "last_name": {
        if (!value) {
          message = "Champ obligatoire.";
        } else if (value.length < 2 || value.length > 40) {
          message = "Doit comporter entre 2 et 40 caractères.";
        }
        break;
      }
      case "first_name": {
        if (value && (value.length < 2 || value.length > 40)) {
          message = "Doit comporter entre 2 et 40 caractères.";
        }
        break;
      }
      case "email": {
        if (value && !emailRegex.test(value)) {
          message = "Email invalide.";
        }
        break;
      }
      case "telephone": {
        if (!value) message = "Champ obligatoire.";
        else if (!phoneRegex.test(value)) {
          message = "Format: 06/05/04 + 7 chiffres (ex: 061234567).";
        }
        break;
      }
      case "username": {
        if (!value) message = "Nom d'utilisateur requis.";
        else if (!usernameRegex.test(value)) {
          message = "3–20 caractères, lettres/chiffres/._, commence par une lettre.";
        }
        break;
      }
      case "password": {
        // ✅ MODIFICATION : Mot de passe optionnel en mode modification
        if (!editMode && !value) {
          message = "Mot de passe requis.";
        } else if (value && value.length < 6) {
          message = "Minimum 6 caractères.";
        }
        break;
      }
      default:
        break;
    }
    setErrors((prev) => ({ ...prev, [name]: message }));
    return message;
  };

  const handleBaseChange = (e) => {
    const { name, value, files } = e.target;
    if (name === "photo") {
      setBase((p) => ({ ...p, photo: files && files[0] ? files[0] : null }));
    } else {
      setBase((p) => ({ ...p, [name]: value }));
      validateField(name, value);
    }
    setTouched((t) => ({ ...t, [name]: true }));
  };

  // ✅ MODIFICATION : Adapter la vérification unicité pour exclure l'utilisateur actuel
  const handleUsernameBlur = async () => {
    if (!base.username || errors.username) return;
    try {
      setUsernameChecking(true);
      const data = await listUsers({ search: base.username });
      const exists = Array.isArray(data) && data.some((u) => {
        const sameUsername = (u.username || "").toLowerCase() === base.username.toLowerCase();
        // ✅ En mode modification, ignorer l'utilisateur actuel
        const isCurrentUser = editMode && u.id === parseInt(editUserId);
        return sameUsername && !isCurrentUser;
      });
      if (exists) {
        setErrors((e) => ({ ...e, username: "Nom d'utilisateur déjà utilisé." }));
      }
    } catch (_) {
    } finally {
      setUsernameChecking(false);
    }
  };

  const handleEmailBlur = async () => {
    if (!base.email || errors.email || !emailRegex.test(base.email)) return;
    try {
      setEmailChecking(true);
      const data = await listUsers({ search: base.email });
      const exists = Array.isArray(data) && data.some((u) => {
        const sameEmail = (u.email || "").toLowerCase() === base.email.toLowerCase();
        // ✅ En mode modification, ignorer l'utilisateur actuel
        const isCurrentUser = editMode && u.id === parseInt(editUserId);
        return sameEmail && !isCurrentUser;
      });
      if (exists) setErrors((e) => ({ ...e, email: "Email déjà utilisé." }));
    } catch (_) {
    } finally {
      setEmailChecking(false);
    }
  };

  const handlePhoneBlur = async () => {
    if (!base.telephone || errors.telephone || !phoneRegex.test(base.telephone)) return;
    try {
      setPhoneChecking(true);
      const data = await listUsers({ search: base.telephone });
      const exists = Array.isArray(data) && data.some((u) => {
        const samePhone = (u.telephone || "") === base.telephone;
        // ✅ En mode modification, ignorer l'utilisateur actuel
        const isCurrentUser = editMode && u.id === parseInt(editUserId);
        return samePhone && !isCurrentUser;
      });
      if (exists) {
        setErrors((e) => ({ ...e, telephone: "Téléphone déjà utilisé." }));
      }
    } catch (_) {
    } finally {
      setPhoneChecking(false);
    }
  };

  // ✅ MODIFICATION MAJEURE : handleSubmit adapté pour création ET modification
  const handleSubmit = async (e) => {
    e.preventDefault();
    setIsSubmitting(true);
    try {
      const newErrors = {};

      // ✅ Validation des champs obligatoires
      const requiredFields = ["last_name", "telephone", "username"];
      // ✅ Mot de passe requis seulement en mode création
      if (!editMode) {
        requiredFields.push("password");
      }

      requiredFields.forEach((f) => {
        const msg = validateField(f, base[f]);
        if (msg) newErrors[f] = msg;
      });

      // Validation des champs existants mais optionnels
      if (errors.username) newErrors.username = errors.username;
      if (base.email && errors.email) newErrors.email = errors.email;
      if (base.first_name && errors.first_name) newErrors.first_name = errors.first_name;

      // ✅ Validation spécifique MÉDECIN
      if (role === "medecin") {
        if (!medecin.specialite) {
          newErrors.specialite = "Spécialité obligatoire pour un médecin.";
        } else if (medecin.specialite.length < 2 || medecin.specialite.length > 60) {
          newErrors.specialite = "Spécialité: 2 à 60 caractères.";
        }
        
        if (!medecin.numero_ordre) {
          newErrors.numero_ordre = "Numéro d'ordre obligatoire pour un médecin.";
        } else if (medecin.numero_ordre.length < 4 || medecin.numero_ordre.length > 30) {
          newErrors.numero_ordre = "Numéro d'ordre: 4 à 30 caractères.";
        }
        
        if (!medecin.service) {
          newErrors.service = "Service obligatoire pour un médecin.";
        }
      }

      if (base.photo && base.photo.size > 2 * 1024 * 1024) {
        newErrors.photo = "Taille maximale 2 Mo.";
      }

      if (Object.keys(newErrors).length > 0) {
        setErrors((prev) => ({ ...prev, ...newErrors }));
        setIsSubmitting(false);
        return;
      }

      // ✅ LOGIQUE DIFFÉRENTE : Création vs Modification
      if (editMode) {
        // **MODE MODIFICATION**
        console.log("Mode modification - ID:", editUserId);
        
        const formData = new FormData();
        formData.append('username', base.username);
        formData.append('email', base.email);
        formData.append('first_name', base.first_name);
        formData.append('last_name', base.last_name);
        formData.append('telephone', base.telephone);
        formData.append('role', role);
        
        // ✅ Mot de passe optionnel en modification
        if (base.password) {
          formData.append('password', base.password);
        }
        
        // ✅ Photo optionnelle en modification
        if (base.photo) {
          formData.append('photo', base.photo);
        }

        console.log("Mise à jour utilisateur avec FormData");
        const updated = await updateUser(editUserId, formData);
        console.log("Utilisateur mis à jour:", updated);

        // ✅ Mise à jour spécifique médecin si nécessaire
        if (role === "medecin") {
          try {
            const medecins = await listMedecins();
            const medecinProfile = medecins.find(m => m.utilisateur?.id === parseInt(editUserId));
            
            if (medecinProfile) {
              console.log("Mise à jour profil médecin ID:", medecinProfile.id);
              await updateMedecin(medecinProfile.id, {
                specialite: medecin.specialite || "Non spécifiée",
                numero_ordre: medecin.numero_ordre || null,
                service: medecin.service || null,
              });
            }
          } catch (err) {
            console.error("Erreur mise à jour profil médecin:", err);
          }
        }

        await Swal.fire({
          title: "Succès",
          text: "Utilisateur modifié avec succès.",
          icon: "success",
          confirmButtonText: "OK",
        });

        // ✅ Retour à la liste après modification
        window.location.href = "/utilisateurs/liste";

      } else {
        // **MODE CRÉATION** (code existant)
        console.log("Mode création");
        
        const formData = new FormData();
        formData.append('username', base.username);
        formData.append('email', base.email);
        formData.append('first_name', base.first_name);
        formData.append('last_name', base.last_name);
        formData.append('telephone', base.telephone);
        formData.append('password', base.password);
        formData.append('role', role);
        
        if (base.photo) {
          formData.append('photo', base.photo);
        }

        console.log("Création nouvel utilisateur avec FormData");
        const created = await createUser(formData);
        console.log("Utilisateur créé:", created);

        // Gestion du profil médecin
        if (role === "medecin" && created?.id) {
          if (created.medecin_profile?.id) {
            await updateMedecin(created.medecin_profile.id, {
              specialite: medecin.specialite || "Non spécifiée",
              numero_ordre: medecin.numero_ordre || null,
              service: medecin.service || null,
            });
          } else {
            try {
              const medecins = await listMedecins();
              const medecinProfile = medecins.find(m => m.utilisateur?.id === created.id);
              if (medecinProfile) {
                await updateMedecin(medecinProfile.id, {
                  specialite: medecin.specialite || "Non spécifiée",
                  numero_ordre: medecin.numero_ordre || null,
                  service: medecin.service || null,
                });
              }
            } catch (err) {
              console.error("Erreur lors de la mise à jour du profil médecin:", err);
            }
          }
        }

        // Reset du formulaire
        setBase({
          username: "",
          email: "",
          first_name: "",
          last_name: "",
          telephone: "",
          password: "",
          photo: null,
        });
        setMedecin({ specialite: "", numero_ordre: "", service: "" });
        setRole("accueil");
        setErrors({});
        setTouched({});
        
        await Swal.fire({
          title: "Succès",
          text: "Utilisateur créé avec succès.",
          icon: "success",
          confirmButtonText: "OK",
        });
      }
    } catch (err) {
      console.error("Erreur lors de l'opération:", err);
      const detail = err?.response?.data;
      if (detail && typeof detail === "object") {
        const mapped = {};
        Object.entries(detail).forEach(([k, v]) => {
          mapped[k] = Array.isArray(v) ? v.join(" ") : String(v);
        });
        setErrors((prev) => ({ ...prev, ...mapped }));
        alert("Veuillez corriger les erreurs dans le formulaire.");
      } else {
        await Swal.fire({
          title: "Erreur",
          text: editMode ? "Erreur lors de la modification" : "Erreur lors de la création",
          icon: "error",
        });
      }
    } finally {
      setIsSubmitting(false);
    }
  };

  const handleCancel = () => {
    if (editMode) {
      // ✅ En mode modification, retourner à la liste
      window.location.href = "/utilisateurs/liste";
    } else {
      // ✅ En mode création, réinitialiser le formulaire
      setBase({
        username: "",
        email: "",
        first_name: "",
        last_name: "",
        telephone: "",
        password: "",
        photo: null,
      });
      setMedecin({ specialite: "", numero_ordre: "", service: "" });
      setRole("accueil");
      setErrors({});
      setTouched({});
    }
  };

  // ✅ Affichage du loader pendant le chargement des données
  if (isLoading) {
    return (
      <div className="flex justify-center items-center h-screen">
        <div className="text-lg">Chargement des données...</div>
      </div>
    );
  }

  return (
    <div>
      <div className="mb-10 ml-60 mt-[70px]">
        {/* ✅ TITRE ADAPTATIF */}
        <p className="text-bold text-xl ml-10">
          {editMode ? "Modifier un utilisateur" : "Ajouter un utilisateur"}
        </p>
        <div className="w-[92%] bg-slate-50 mt-7 rounded-sm px-5 pt-7 pb-8 mx-12">
          <form onSubmit={handleSubmit}>
            <div className="mb-7 text-[17px] font-semibold text-gray-600">
              Informations de base
            </div>
            <div className="flex sm:flex-row flex-col">
              <div className="grow sm:mr-5 sm:mb-0 mb-4">
                <input
                  name="last_name"
                  value={base.last_name}
                  onChange={handleBaseChange}
                  type="text"
                  className={`w-full border ${
                    errors.last_name ? "border-red-500" : "border-gray-300"
                  } rounded-full h-[35px] pl-3`}
                  placeholder="Nom"
                  required
                />
                <p className="text-xs text-gray-500 mt-1">2–40 caractères.</p>
                {touched.last_name && errors.last_name && (
                  <p className="text-xs text-red-600 mt-1">
                    {errors.last_name}
                  </p>
                )}
              </div>
              <div className="grow sm:ml-5">
                <input
                  name="first_name"
                  value={base.first_name}
                  onChange={handleBaseChange}
                  type="text"
                  className={`w-full border ${
                    errors.first_name ? "border-red-500" : "border-gray-300"
                  } rounded-full h-[35px] pl-3`}
                  placeholder="Prénom (facultatif)"
                />
                <p className="text-xs text-gray-500 mt-1">2–40 caractères (facultatif).</p>
                {touched.first_name && errors.first_name && (
                  <p className="text-xs text-red-600 mt-1">
                    {errors.first_name}
                  </p>
                )}
              </div>
            </div>

            <div className="flex sm:flex-row flex-col sm:mt-5 mt-3">
              <div className="grow sm:mr-5 sm:mb-0 mb-4">
                <input
                  name="email"
                  value={base.email}
                  onChange={handleBaseChange}
                  onBlur={handleEmailBlur}
                  type="email"
                  className={`w-full border ${
                    errors.email ? "border-red-500" : "border-gray-300"
                  } rounded-full h-[35px] pl-3`}
                  placeholder="Email (facultatif)"
                />
                <p className="text-xs text-gray-500 mt-1">
                  Unique s'il est renseigné. Doit être valide.
                </p>
                {emailChecking && (
                  <p className="text-xs text-gray-400 mt-1">
                    Vérification de disponibilité…
                  </p>
                )}
                {touched.email && errors.email && (
                  <p className="text-xs text-red-600 mt-1">{errors.email}</p>
                )}
              </div>
              <div className="grow sm:ml-5">
                <div className="flex items-center border rounded-full h-[35px] px-3 bg-white border-gray-300">
                  <span className="text-gray-600 mr-2">+242</span>
                  <input
                    name="telephone"
                    value={base.telephone}
                    onChange={handleBaseChange}
                    onBlur={handlePhoneBlur}
                    type="text"
                    className={`flex-1 outline-none ${
                      errors.telephone ? "text-red-600" : ""
                    }`}
                    placeholder="061234567"
                    required
                    maxLength="9"
                  />
                </div>
                <p className="text-xs text-gray-500 mt-1">
                  Format: 06/05/04 + 7 chiffres (ex: 061234567). Doit être
                  unique.
                </p>
                {phoneChecking && (
                  <p className="text-xs text-gray-400 mt-1">
                    Vérification de disponibilité…
                  </p>
                )}
                {touched.telephone && errors.telephone && (
                  <p className="text-xs text-red-600 mt-1">
                    {errors.telephone}
                  </p>
                )}
              </div>
            </div>

            <div className="flex sm:flex-row flex-col sm:mt-5 mt-3">
              <div className="grow sm:mr-5 sm:mb-0 mb-4">
                <input
                  name="username"
                  value={base.username}
                  onChange={handleBaseChange}
                  onBlur={handleUsernameBlur}
                  type="text"
                  className={`w-full border ${
                    errors.username ? "border-red-500" : "border-gray-300"
                  } rounded-full h-[35px] pl-3`}
                  placeholder="Nom d'utilisateur"
                  required
                />
                <p className="text-xs text-gray-500 mt-1">
                  3–20 caractères, lettres/chiffres/._, commence par une lettre.
                </p>
                {usernameChecking && (
                  <p className="text-xs text-gray-400 mt-1">
                    Vérification de disponibilité…
                  </p>
                )}
                {touched.username && errors.username && (
                  <p className="text-xs text-red-600 mt-1">{errors.username}</p>
                )}
              </div>
              <div className="grow sm:ml-5">
                <input
                  name="password"
                  value={base.password}
                  onChange={handleBaseChange}
                  type="password"
                  className={`w-full border ${
                    errors.password ? "border-red-500" : "border-gray-300"
                  } rounded-full h-[35px] pl-3`}
                  placeholder={editMode ? "Nouveau mot de passe (facultatif)" : "Mot de passe"}
                  required={!editMode}
                />
                <p className="text-xs text-gray-500 mt-1">
                  {editMode 
                    ? "Laisser vide pour conserver le mot de passe actuel. Minimum 6 caractères si renseigné."
                    : "Mot de passe minimum 6 caractères."
                  }
                </p>
                {touched.password && errors.password && (
                  <p className="text-xs text-red-600 mt-1">{errors.password}</p>
                )}
              </div>
            </div>

            <div className="flex sm:flex-row flex-col sm:mt-5 mt-3">
              <select
                value={role}
                onChange={(e) => setRole(e.target.value)}
                className="grow sm:mb-0 mb-4 sm:mr-5 border border-gray-300 rounded-full h-[35px] pl-3"
                required
              >
                <option value="admin">Administrateur</option>
                <option value="medecin">Médecin</option>
                <option value="accueil">Accueil</option>
                <option value="orientation">Orientation</option>
              </select>
              <div className="grow sm:ml-5">
                <label className="block text-gray-600 text-sm mb-1">
                  {editMode ? "Nouvelle photo (PNG/JPG, max 2 Mo) - Facultatif" : "Photo (PNG/JPG, max 2 Mo) - Facultatif"}
                </label>
                <input
                  name="photo"
                  onChange={handleBaseChange}
                  type="file"
                  accept="image/*"
                  className="w-full border border-gray-300 rounded-full h-[35px] pl-3 pr-3 bg-white file:mr-3 file:py-1 file:px-2 file:rounded-full file:border-0 file:bg-gray-200 file:text-gray-700"
                />
                {editMode && (
                  <p className="text-xs text-gray-500 mt-1">
                    Laisser vide pour conserver la photo actuelle.
                  </p>
                )}
                {errors.photo && (
                  <p className="text-xs text-red-600 mt-1">{errors.photo}</p>
                )}
              </div>
            </div>

            {role === "medecin" && (
              <div className="mt-12">
                <div className="mb-3 text-[17px] font-semibold text-gray-600">
                  Informations spécifiques (Médecin) *
                </div>
                <div className="flex sm:flex-row flex-col">
                  <div className="grow sm:mr-5 sm:mb-0 mb-4">
                    <input
                      value={medecin.specialite}
                      onChange={(e) =>
                        setMedecin((p) => ({
                          ...p,
                          specialite: e.target.value,
                        }))
                      }
                      type="text"
                      className={`w-full border ${
                        errors.specialite ? "border-red-500" : "border-gray-300"
                      } rounded-full h-[35px] pl-3`}
                      placeholder="Spécialité *"
                      required
                    />
                    <p className="text-xs text-gray-500 mt-1">
                      2–60 caractères (obligatoire).
                    </p>
                    {errors.specialite && (
                      <p className="text-xs text-red-600 mt-1">
                        {errors.specialite}
                      </p>
                    )}
                  </div>
                  <div className="grow sm:ml-5">
                    <input
                      value={medecin.numero_ordre}
                      onChange={(e) =>
                        setMedecin((p) => ({
                          ...p,
                          numero_ordre: e.target.value,
                        }))
                      }
                      type="text"
                      className={`w-full border ${
                        errors.numero_ordre
                          ? "border-red-500"
                          : "border-gray-300"
                      } rounded-full h-[35px] pl-3`}
                      placeholder="Numéro d'ordre *"
                      required
                    />
                    <p className="text-xs text-gray-500 mt-1">
                      4–30 caractères (obligatoire).
                    </p>
                    {errors.numero_ordre && (
                      <p className="text-xs text-red-600 mt-1">
                        {errors.numero_ordre}
                      </p>
                    )}
                  </div>
                </div>
                <div className="flex sm:flex-row flex-col sm:mt-5 mt-3">
                  <select
                    value={medecin.service}
                    onChange={(e) =>
                      setMedecin((p) => ({ ...p, service: e.target.value }))
                    }
                    className={`grow sm:mb-0 mb-4 sm:mr-5 border ${
                      errors.service ? "border-red-500" : "border-gray-300"
                    } rounded-full h-[35px] pl-3`}
                    required
                  >
                    <option value="" disabled hidden>
                      Service * (obligatoire)
                    </option>
                    {services.map((s) => (
                      <option key={s.id} value={s.id}>
                        {s.nom}
                      </option>
                    ))}
                  </select>
                  {errors.service && (
                    <p className="text-xs text-red-600 mt-1">{errors.service}</p>
                  )}
                </div>
              </div>
            )}

            <hr className="mt-8" />
            <div className="flex pt-5">
              <button
                type="button"
                onClick={handleCancel}
                className="border rounded-full px-4 py-2 border-gray-400 text-gray-400 text-sm mr-3"
              >
                {editMode ? "Retour à la liste" : "Annuler"}
              </button>
              <button
                type="submit"
                disabled={isSubmitting}
                className="rounded-full bg-orange-500 px-4 py-2 text-sm text-white disabled:opacity-50"
              >
                {isSubmitting 
                  ? (editMode ? "Modification..." : "Enregistrement...")
                  : (editMode ? "Modifier" : "Enregistrer")
                }
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  );
}

