import { useEffect, useState } from "react";
import { useNavigate, useParams } from "react-router-dom";
//import { http } from "../../services/api/Users";
import UserForm from "./UserForm";

export default function UserEdit() {
  const { id } = useParams();
  const nav = useNavigate();

  const [initial, setInitial] = useState(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    (async () => {
      const { data } = await http.get(`/users/${id}`);
      setInitial({
        name: data.name,
        email: data.email,
        roles: (data.roles || []).map((r) => r.name),
        permissions: (data.permissions || []).map((p) => p.name),
      });
      setLoading(false);
    })();
  }, [id]);

  async function onSubmit(payload) {
    await http.put(`/users/${id}`, {
      name: payload.name,
      email: payload.email,
      password: payload.password, // undefined => ignoré par backend
      roles: payload.roles,
      permissions: payload.permissions,
    });
    nav("/users");
  }

  return (
    <div className="container">
      <h1>Modifier utilisateur #{id}</h1>
      {loading ? <div className="card">Chargement...</div> : (
        <UserForm initialValues={initial} submitLabel="Enregistrer" onSubmit={onSubmit} />
      )}
    </div>
  );
}