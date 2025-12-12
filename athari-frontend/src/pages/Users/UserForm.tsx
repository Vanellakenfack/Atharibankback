import { useMemo, useState } from "react";

const ROLES = [
  "DG",
  "Admin",
  "Chef Comptable",
  "Chef d'Agence (CA)",
  "Assistant Juridique (AJ)",
  "Assistant Comptable (AC)",
  "Caissière",
  "Agent de Crédit (AC)",
  "Collecteur",
  "Audit/Contrôle (IV)",
];

export default function UserForm({ initialValues, onSubmit, submitLabel }) {
  const [form, setForm] = useState(() => ({
    name: initialValues?.name || "",
    email: initialValues?.email || "",
    password: "",
    roles: initialValues?.roles || [],
    // permissions optionnelles: input texte (CSV)
    permissionsText: (initialValues?.permissions || []).join(", "),
  }));

  const permissions = useMemo(() => {
    return form.permissionsText
      .split(",")
      .map((s) => s.trim())
      .filter(Boolean);
  }, [form.permissionsText]);

  function toggleRole(role) {
    setForm((s) => {
      const exists = s.roles.includes(role);
      return { ...s, roles: exists ? s.roles.filter((r) => r !== role) : [...s.roles, role] };
    });
  }

  async function handleSubmit(e) {
    e.preventDefault();
    await onSubmit({
      name: form.name,
      email: form.email,
      password: form.password || undefined, // si vide => on n’envoie pas
      roles: form.roles,
      permissions,
    });
  }

  return (
    <form className="card" onSubmit={handleSubmit}>
      <label>Nom</label>
      <input
        value={form.name}
        onChange={(e) => setForm((s) => ({ ...s, name: e.target.value }))}
        required
      />

      <label>Email</label>
      <input
        type="email"
        value={form.email}
        onChange={(e) => setForm((s) => ({ ...s, email: e.target.value }))}
        required
      />

      <label>Mot de passe (laisser vide pour ne pas changer)</label>
      <input
        type="password"
        value={form.password}
        onChange={(e) => setForm((s) => ({ ...s, password: e.target.value }))}
        placeholder="******"
      />

      <label>Rôles (Spatie)</label>
      <div className="grid">
        {ROLES.map((r) => (
          <label key={r} className="checkbox">
            <input
              type="checkbox"
              checked={form.roles.includes(r)}
              onChange={() => toggleRole(r)}
            />
            <span>{r}</span>
          </label>
        ))}
      </div>

      <label>Permissions (optionnel, CSV)</label>
      <input
        value={form.permissionsText}
        onChange={(e) => setForm((s) => ({ ...s, permissionsText: e.target.value }))}
        placeholder="gerer utilisateurs, consulter logs"
      />

      <button type="submit">{submitLabel}</button>
    </form>
  );
}