import { useNavigate } from "react-router-dom";
//import { http } from "../../services/api/Users";
import UserForm from "./UserForm";

export default function UserCreate() {
  const nav = useNavigate();

  async function onSubmit(payload) {
    await http.post("/users", {
      name: payload.name,
      email: payload.email,
      password: payload.password ?? "password", // obligatoire côté backend
      roles: payload.roles,
      permissions: payload.permissions,
    });
    nav("/users");
  }

  return (
    <div className="container">
      <h1>Nouveau utilisateur</h1>
      <UserForm submitLabel="Créer" onSubmit={onSubmit} />
    </div>
  );
}