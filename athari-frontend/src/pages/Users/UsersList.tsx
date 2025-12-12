import { useEffect, useMemo, useRef, useState } from "react";

/**
 * CRUD Users (Front-only)
 * - Create / Read / Update / Delete
 * - Search + filter role + pagination
 * - Data persisted in localStorage (no backend calls)
 */

const STORAGE_KEY = "users_crud_tailwind_v1";

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

const PERMISSIONS = [
  "gerer utilisateurs",
  "gerer roles et permissions",
  "consulter logs",
  "valider credits",
  "superviser agences",
  "encaisser",
  "collecter",
];

function uid() {
  // simple unique id for demo
  return Math.random().toString(16).slice(2) + "-" + Date.now().toString(16);
}

function loadUsers() {
  try {
    const raw = localStorage.getItem(STORAGE_KEY);
    if (!raw) return null;
    const parsed = JSON.parse(raw);
    if (!Array.isArray(parsed)) return null;
    return parsed;
  } catch {
    return null;
  }
}

function saveUsers(users) {
  localStorage.setItem(STORAGE_KEY, JSON.stringify(users));
}

function classNames(...xs) {
  return xs.filter(Boolean).join(" ");
}

function Badge({ children, tone = "slate" }) {
  const tones = {
    slate: "bg-slate-100 text-slate-700 ring-slate-200",
    blue: "bg-blue-50 text-blue-700 ring-blue-200",
    green: "bg-emerald-50 text-emerald-700 ring-emerald-200",
    red: "bg-rose-50 text-rose-700 ring-rose-200",
    amber: "bg-amber-50 text-amber-800 ring-amber-200",
  };

  return (
    <span
      className={classNames(
        "inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium ring-1 ring-inset",
        tones[tone] || tones.slate
      )}
    >
      {children}
    </span>
  );
}

function Button({ variant = "primary", className = "", ...props }) {
  const base =
    "inline-flex items-center justify-center rounded-lg px-3 py-2 text-sm font-semibold transition focus:outline-none focus:ring-2 focus:ring-slate-900/20 disabled:opacity-50 disabled:cursor-not-allowed";

  const variants = {
    primary: "bg-slate-900 text-white hover:bg-slate-800",
    secondary: "bg-white text-slate-900 ring-1 ring-inset ring-slate-200 hover:bg-slate-50",
    danger: "bg-rose-600 text-white hover:bg-rose-500",
    ghost: "bg-transparent text-slate-700 hover:bg-slate-100",
  };

  return <button className={classNames(base, variants[variant], className)} {...props} />;
}

function Input({ label, hint, error, className = "", ...props }) {
  return (
    <div className={className}>
      {label && (
        <div className="flex items-center justify-between">
          <label className="text-sm font-medium text-slate-800">{label}</label>
          {hint && <span className="text-xs text-slate-500">{hint}</span>}
        </div>
      )}
      <input
        className={classNames(
          "mt-1 w-full rounded-lg border bg-white px-3 py-2 text-sm outline-none transition",
          error
            ? "border-rose-300 focus:border-rose-400 focus:ring-2 focus:ring-rose-100"
            : "border-slate-200 focus:border-slate-300 focus:ring-2 focus:ring-slate-100"
        )}
        {...props}
      />
      {error && <p className="mt-1 text-xs text-rose-700">{error}</p>}
    </div>
  );
}

function Select({ label, className = "", children, ...props }) {
  return (
    <div className={className}>
      {label && <label className="text-sm font-medium text-slate-800">{label}</label>}
      <select
        className="mt-1 w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm outline-none transition focus:border-slate-300 focus:ring-2 focus:ring-slate-100"
        {...props}
      >
        {children}
      </select>
    </div>
  );
}

function Modal({ open, title, description, children, onClose }) {
  const panelRef = useRef(null);

  useEffect(() => {
    function onKeyDown(e) {
      if (!open) return;
      if (e.key === "Escape") onClose?.();
    }
    window.addEventListener("keydown", onKeyDown);
    return () => window.removeEventListener("keydown", onKeyDown);
  }, [open, onClose]);

  useEffect(() => {
    if (open) {
      setTimeout(() => panelRef.current?.focus?.(), 0);
    }
  }, [open]);

  if (!open) return null;

  return (
    <div className="fixed inset-0 z-50">
      <div
        className="absolute inset-0 bg-slate-900/40 backdrop-blur-[1px]"
        onMouseDown={onClose}
      />
      <div className="absolute inset-0 flex items-center justify-center p-4">
        <div
          ref={panelRef}
          tabIndex={-1}
          className="w-full max-w-2xl rounded-2xl bg-white shadow-xl ring-1 ring-slate-200 focus:outline-none"
          onMouseDown={(e) => e.stopPropagation()}
        >
          <div className="border-b border-slate-100 px-5 py-4">
            <div className="flex items-start justify-between gap-4">
              <div>
                <h2 className="text-base font-semibold text-slate-900">{title}</h2>
                {description && <p className="mt-1 text-sm text-slate-600">{description}</p>}
              </div>
              <Button variant="ghost" onClick={onClose} aria-label="Fermer">
                ✕
              </Button>
            </div>
          </div>
          <div className="px-5 py-4">{children}</div>
        </div>
      </div>
    </div>
  );
}

function ConfirmDialog({ open, title, message, confirmText = "Confirmer", onConfirm, onClose }) {
  return (
    <Modal open={open} title={title} description={message} onClose={onClose}>
      <div className="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
        <Button variant="secondary" onClick={onClose}>
          Annuler
        </Button>
        <Button
          variant="danger"
          onClick={() => {
            onConfirm?.();
            onClose?.();
          }}
        >
          {confirmText}
        </Button>
      </div>
    </Modal>
  );
}

function RolesPicker({ value, onChange }) {
  return (
    <div>
      <div className="flex items-center justify-between">
        <label className="text-sm font-medium text-slate-800">Rôles</label>
        <span className="text-xs text-slate-500">Multi-sélection</span>
      </div>
      <div className="mt-2 grid grid-cols-1 gap-2 sm:grid-cols-2">
        {ROLES.map((r) => {
          const checked = value.includes(r);
          return (
            <label
              key={r}
              className={classNames(
                "flex items-center gap-2 rounded-lg border px-3 py-2 text-sm transition",
                checked ? "border-slate-900 bg-slate-900/5" : "border-slate-200 bg-white"
              )}
            >
              <input
                type="checkbox"
                className="h-4 w-4 rounded border-slate-300 text-slate-900 focus:ring-slate-200"
                checked={checked}
                onChange={() => {
                  if (checked) onChange(value.filter((x) => x !== r));
                  else onChange([...value, r]);
                }}
              />
              <span className="text-slate-800">{r}</span>
            </label>
          );
        })}
      </div>
    </div>
  );
}

function PermissionsPicker({ value, onChange }) {
  return (
    <div>
      <div className="flex items-center justify-between">
        <label className="text-sm font-medium text-slate-800">Permissions</label>
        <span className="text-xs text-slate-500">Optionnel</span>
      </div>
      <div className="mt-2 flex flex-wrap gap-2">
        {PERMISSIONS.map((p) => {
          const active = value.includes(p);
          return (
            <button
              key={p}
              type="button"
              onClick={() => {
                if (active) onChange(value.filter((x) => x !== p));
                else onChange([...value, p]);
              }}
              className={classNames(
                "rounded-full px-3 py-1 text-xs font-semibold ring-1 ring-inset transition",
                active
                  ? "bg-emerald-50 text-emerald-700 ring-emerald-200"
                  : "bg-white text-slate-700 ring-slate-200 hover:bg-slate-50"
              )}
            >
              {p}
            </button>
          );
        })}
      </div>
    </div>
  );
}

function UserFormModal({ open, mode, initialUser, onClose, onSubmit }) {
  const isEdit = mode === "edit";

  const [form, setForm] = useState(() => ({
    name: "",
    email: "",
    password: "",
    roles: [],
    permissions: [],
    status: "active",
  }));

  const [errors, setErrors] = useState({});

  useEffect(() => {
    if (!open) return;

    setErrors({});
    setForm({
      name: initialUser?.name || "",
      email: initialUser?.email || "",
      password: "",
      roles: initialUser?.roles || [],
      permissions: initialUser?.permissions || [],
      status: initialUser?.status || "active",
    });
  }, [open, initialUser]);

  function validate() {
    const e = {};
    if (!form.name.trim()) e.name = "Le nom est requis.";
    if (!form.email.trim()) e.email = "L'email est requis.";
    if (form.email && !/^\S+@\S+\.\S+$/.test(form.email)) e.email = "Email invalide.";

    if (!isEdit) {
      if (!form.password) e.password = "Le mot de passe est requis en création.";
      if (form.password && form.password.length < 6) e.password = "Minimum 6 caractères.";
    } else {
      if (form.password && form.password.length < 6) e.password = "Minimum 6 caractères.";
    }

    setErrors(e);
    return Object.keys(e).length === 0;
  }

  return (
    <Modal
      open={open}
      title={isEdit ? "Modifier utilisateur" : "Créer utilisateur"}
      description="Renseigne les champs puis enregistre."
      onClose={onClose}
    >
      <form
        className="space-y-4"
        onSubmit={(ev) => {
          ev.preventDefault();
          if (!validate()) return;
          onSubmit?.({
            ...initialUser,
            name: form.name.trim(),
            email: form.email.trim().toLowerCase(),
            // Front-only: on conserve password uniquement si fourni (edit)
            password: form.password ? form.password : undefined,
            roles: form.roles,
            permissions: form.permissions,
            status: form.status,
          });
          onClose?.();
        }}
      >
        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
          <Input
            label="Nom"
            value={form.name}
            error={errors.name}
            onChange={(e) => setForm((s) => ({ ...s, name: e.target.value }))}
            placeholder="Ex: Jean Dupont"
          />

          <Input
            label="Email"
            value={form.email}
            error={errors.email}
            onChange={(e) => setForm((s) => ({ ...s, email: e.target.value }))}
            placeholder="ex: jean@example.com"
          />

          <Input
            label="Mot de passe"
            hint={isEdit ? "Laisse vide pour ne pas changer" : "Obligatoire"}
            value={form.password}
            error={errors.password}
            onChange={(e) => setForm((s) => ({ ...s, password: e.target.value }))}
            type="password"
            placeholder={isEdit ? "••••••••" : "Min 6 caractères"}
            className="sm:col-span-2"
          />

          <Select
            label="Statut"
            value={form.status}
            onChange={(e) => setForm((s) => ({ ...s, status: e.target.value }))}
            className="sm:col-span-2"
          >
            <option value="active">Actif</option>
            <option value="disabled">Désactivé</option>
          </Select>
        </div>

        <div className="grid grid-cols-1 gap-4 lg:grid-cols-2">
          <RolesPicker value={form.roles} onChange={(roles) => setForm((s) => ({ ...s, roles }))} />
          <PermissionsPicker
            value={form.permissions}
            onChange={(permissions) => setForm((s) => ({ ...s, permissions }))}
          />
        </div>

        <div className="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
          <Button type="button" variant="secondary" onClick={onClose}>
            Annuler
          </Button>
          <Button type="submit">{isEdit ? "Enregistrer" : "Créer"}</Button>
        </div>
      </form>
    </Modal>
  );
}

function EmptyState({ title, subtitle, action }) {
  return (
    <div className="rounded-2xl border border-slate-200 bg-white p-10 text-center">
      <h3 className="text-base font-semibold text-slate-900">{title}</h3>
      <p className="mt-2 text-sm text-slate-600">{subtitle}</p>
      {action && <div className="mt-4">{action}</div>}
    </div>
  );
}

export default function UsersPage() {
  const [users, setUsers] = useState(() => {
    const saved = loadUsers();
    if (saved) return saved;

    // Seed front-only
    const now = new Date().toISOString();
    return [
      {
        id: uid(),
        name: "Admin Principal",
        email: "admin@example.com",
        roles: ["Admin"],
        permissions: ["gerer utilisateurs", "gerer roles et permissions", "consulter logs"],
        status: "active",
        createdAt: now,
        updatedAt: now,
      },
      {
        id: uid(),
        name: "Directeur Général",
        email: "dg@example.com",
        roles: ["DG"],
        permissions: ["superviser agences", "consulter logs"],
        status: "active",
        createdAt: now,
        updatedAt: now,
      },
    ];
  });

  // persist (still no backend)
  useEffect(() => saveUsers(users), [users]);

  const [query, setQuery] = useState("");
  const [roleFilter, setRoleFilter] = useState("all");
  const [statusFilter, setStatusFilter] = useState("all");

  const [page, setPage] = useState(1);
  const pageSize = 8;

  const [modalOpen, setModalOpen] = useState(false);
  const [modalMode, setModalMode] = useState("create"); // create | edit
  const [selected, setSelected] = useState(null);

  const [confirmOpen, setConfirmOpen] = useState(false);
  const [toDelete, setToDelete] = useState(null);

  const filtered = useMemo(() => {
    const q = query.trim().toLowerCase();

    return users
      .filter((u) => {
        const matchQ =
          !q ||
          u.name.toLowerCase().includes(q) ||
          u.email.toLowerCase().includes(q) ||
          (u.roles || []).some((r) => r.toLowerCase().includes(q)) ||
          (u.permissions || []).some((p) => p.toLowerCase().includes(q));

        const matchRole = roleFilter === "all" ? true : (u.roles || []).includes(roleFilter);
        const matchStatus = statusFilter === "all" ? true : u.status === statusFilter;

        return matchQ && matchRole && matchStatus;
      })
      .sort((a, b) => (a.updatedAt < b.updatedAt ? 1 : -1));
  }, [users, query, roleFilter, statusFilter]);

  const totalPages = Math.max(1, Math.ceil(filtered.length / pageSize));
  const currentPage = Math.min(page, totalPages);

  const pageItems = useMemo(() => {
    const start = (currentPage - 1) * pageSize;
    return filtered.slice(start, start + pageSize);
  }, [filtered, currentPage]);

  useEffect(() => {
    // reset page on filters change
    setPage(1);
  }, [query, roleFilter, statusFilter]);

  function openCreate() {
    setSelected(null);
    setModalMode("create");
    setModalOpen(true);
  }

  function openEdit(user) {
    setSelected(user);
    setModalMode("edit");
    setModalOpen(true);
  }

  function upsertUser(payload) {
    const now = new Date().toISOString();

    setUsers((prev) => {
      if (modalMode === "edit") {
        return prev.map((u) =>
          u.id === payload.id
            ? {
                ...u,
                name: payload.name,
                email: payload.email,
                roles: payload.roles,
                permissions: payload.permissions,
                status: payload.status,
                updatedAt: now,
              }
            : u
        );
      }

      // create
      const newUser = {
        id: uid(),
        name: payload.name,
        email: payload.email,
        roles: payload.roles,
        permissions: payload.permissions,
        status: payload.status,
        createdAt: now,
        updatedAt: now,
      };
      return [newUser, ...prev];
    });
  }

  function requestDelete(user) {
    setToDelete(user);
    setConfirmOpen(true);
  }

  function deleteUser() {
    if (!toDelete) return;
    setUsers((prev) => prev.filter((u) => u.id !== toDelete.id));
    setToDelete(null);
  }

  return (
    <div className="min-h-screen">
      <header className="border-b border-slate-200 bg-white">
        <div className="mx-auto flex max-w-6xl items-center justify-between gap-4 px-4 py-4 sm:px-6">
          <div>
            <h1 className="text-lg font-semibold text-slate-900">Gestion des utilisateurs</h1>
            <p className="mt-1 text-sm text-slate-600">
              CRUD Front-end (React + Tailwind), sans backend.
            </p>
          </div>

          <div className="flex items-center gap-2">
            <Button variant="secondary" onClick={() => localStorage.removeItem(STORAGE_KEY)}>
              Reset localStorage
            </Button>
            <Button onClick={openCreate}>+ Nouveau</Button>
          </div>
        </div>
      </header>

      <main className="mx-auto max-w-6xl px-4 py-6 sm:px-6">
        <div className="rounded-2xl border border-slate-200 bg-white p-4">
          <div className="flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
            <Input
              label="Recherche"
              value={query}
              onChange={(e) => setQuery(e.target.value)}
              placeholder="Nom, email, rôle, permission..."
              className="md:w-[420px]"
            />

            <div className="grid grid-cols-1 gap-3 sm:grid-cols-3 md:w-[520px]">
              <Select label="Rôle" value={roleFilter} onChange={(e) => setRoleFilter(e.target.value)}>
                <option value="all">Tous</option>
                {ROLES.map((r) => (
                  <option key={r} value={r}>
                    {r}
                  </option>
                ))}
              </Select>

              <Select
                label="Statut"
                value={statusFilter}
                onChange={(e) => setStatusFilter(e.target.value)}
              >
                <option value="all">Tous</option>
                <option value="active">Actif</option>
                <option value="disabled">Désactivé</option>
              </Select>

              <div className="sm:col-span-1">
                <div className="text-sm font-medium text-slate-800">Total</div>
                <div className="mt-1 flex items-center gap-2">
                  <Badge tone="blue">{filtered.length} utilisateur(s)</Badge>
                  <Badge tone="slate">
                    Page {currentPage}/{totalPages}
                  </Badge>
                </div>
              </div>
            </div>
          </div>

          <div className="mt-4 overflow-x-auto">
            {filtered.length === 0 ? (
              <div className="py-6">
                <EmptyState
                  title="Aucun utilisateur"
                  subtitle="Essaie de changer la recherche/les filtres, ou crée un nouvel utilisateur."
                  action={<Button onClick={openCreate}>Créer un utilisateur</Button>}
                />
              </div>
            ) : (
              <table className="min-w-full border-separate border-spacing-0">
                <thead>
                  <tr className="text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                    <th className="border-b border-slate-200 px-3 py-3">Utilisateur</th>
                    <th className="border-b border-slate-200 px-3 py-3">Rôles</th>
                    <th className="border-b border-slate-200 px-3 py-3">Permissions</th>
                    <th className="border-b border-slate-200 px-3 py-3">Statut</th>
                    <th className="border-b border-slate-200 px-3 py-3 text-right">Actions</th>
                  </tr>
                </thead>
                <tbody>
                  {pageItems.map((u) => (
                    <tr key={u.id} className="hover:bg-slate-50/60">
                      <td className="border-b border-slate-100 px-3 py-4 align-top">
                        <div className="font-semibold text-slate-900">{u.name}</div>
                        <div className="mt-1 text-sm text-slate-600">{u.email}</div>
                        <div className="mt-2 text-xs text-slate-500">
                          Modifié: {new Date(u.updatedAt).toLocaleString()}
                        </div>
                      </td>

                      <td className="border-b border-slate-100 px-3 py-4 align-top">
                        <div className="flex flex-wrap gap-2">
                          {(u.roles || []).length ? (
                            u.roles.map((r) => (
                              <Badge key={r} tone="amber">
                                {r}
                              </Badge>
                            ))
                          ) : (
                            <span className="text-sm text-slate-500">—</span>
                          )}
                        </div>
                      </td>

                      <td className="border-b border-slate-100 px-3 py-4 align-top">
                        <div className="flex flex-wrap gap-2">
                          {(u.permissions || []).length ? (
                            u.permissions.slice(0, 4).map((p) => (
                              <Badge key={p} tone="green">
                                {p}
                              </Badge>
                            ))
                          ) : (
                            <span className="text-sm text-slate-500">—</span>
                          )}
                          {(u.permissions || []).length > 4 && (
                            <Badge tone="slate">+{u.permissions.length - 4}</Badge>
                          )}
                        </div>
                      </td>

                      <td className="border-b border-slate-100 px-3 py-4 align-top">
                        {u.status === "active" ? (
                          <Badge tone="green">Actif</Badge>
                        ) : (
                          <Badge tone="red">Désactivé</Badge>
                        )}
                      </td>

                      <td className="border-b border-slate-100 px-3 py-4 align-top">
                        <div className="flex justify-end gap-2">
                          <Button variant="secondary" className="px-2.5 py-2" onClick={() => openEdit(u)}>
                            Modifier
                          </Button>
                          <Button
                            variant="danger"
                            className="px-2.5 py-2"
                            onClick={() => requestDelete(u)}
                          >
                            Supprimer
                          </Button>
                        </div>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            )}
          </div>

          {filtered.length > 0 && (
            <div className="mt-4 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
              <div className="text-sm text-slate-600">
                Affichage{" "}
                <span className="font-semibold text-slate-900">
                  {(currentPage - 1) * pageSize + 1}
                </span>
                {" - "}
                <span className="font-semibold text-slate-900">
                  {Math.min(currentPage * pageSize, filtered.length)}
                </span>{" "}
                sur <span className="font-semibold text-slate-900">{filtered.length}</span>
              </div>

              <div className="flex items-center justify-end gap-2">
                <Button
                  variant="secondary"
                  onClick={() => setPage((p) => Math.max(1, p - 1))}
                  disabled={currentPage <= 1}
                >
                  Précédent
                </Button>
                <Button
                  variant="secondary"
                  onClick={() => setPage((p) => Math.min(totalPages, p + 1))}
                  disabled={currentPage >= totalPages}
                >
                  Suivant
                </Button>
              </div>
            </div>
          )}
        </div>
      </main>

      <UserFormModal
        open={modalOpen}
        mode={modalMode}
        initialUser={selected}
        onClose={() => setModalOpen(false)}
        onSubmit={upsertUser}
      />

      <ConfirmDialog
        open={confirmOpen}
        title="Supprimer utilisateur"
        message={
          toDelete
            ? `Confirmer la suppression de “${toDelete.name}” (${toDelete.email}) ?`
            : "Confirmer ?"
        }
        confirmText="Supprimer"
        onConfirm={deleteUser}
        onClose={() => {
          setConfirmOpen(false);
          setToDelete(null);
        }}
      />
    </div>
  );
}