import React from "react";
import "../assets/css/home.css";
import logo from "../assets/img/logo.png"; 

export default function Home() {
  return (
    <div>
      <div className="financial-bubbles">
        <div className="bubble">$</div>
        <div className="bubble">€</div>
        <div className="bubble">FCFA</div>
        <div className="bubble">£</div>
        <div className="bubble">¥</div>
        <div className="bubble">₹</div>
      </div>

      <header>
        <div className="main">
          <div className="logo">
            <img src={logo} alt="Athari Financial Logo" className="rounded-image" />
          </div>
        </div>

        <div className="title">
          <h1>Bienvenue sur Atharibank</h1>
        </div>

        <div className="button">
          <a href="/login" className="btn"><span>Administrateur</span></a>
          <a href="/login" className="btn"><span>Chef d'Agence</span></a>
          <a href="/login" className="btn"><span>Comptable</span></a>
          <a href="/login" className="btn"><span>Caissière</span></a>
        </div>
      </header>
    </div>
  );
}
