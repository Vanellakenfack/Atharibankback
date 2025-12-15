// src/auth/AuthContext.tsx
import { createContext, useContext, useState } from "react";


interface User {
name: string;
email: string;
role: string;
abilities: string[];
}


const AuthContext = createContext<any>(null);


export const AuthProvider = ({ children }: any) => {
const [user, setUser] = useState<User | null>(null);


const loginUser = (payload: any) => {
localStorage.setItem("token", payload.token);
setUser(payload.user);
};


const logoutUser = () => {
localStorage.removeItem("token");
setUser(null);
};


return (
<AuthContext.Provider value={{ user, loginUser, logoutUser }}>
{children}
</AuthContext.Provider>
);
};


export const useAuth = () => useContext(AuthContext);