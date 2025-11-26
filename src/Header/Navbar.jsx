import React from "react";
import logo from "../assets/bs.png"
const Navbar =()=>{
    return(
       <div className="navbar bg-base-100 shadow-sm">
  <div className="flex-1">
    <a className="btn btn-ghost text-xl"><img className="w-[60px] h-[60px]" src={logo} alt="" /> BlockSight</a>
  </div>
  <div className="flex-none">
    <ul className="menu menu-horizontal px-1">
      <li><a>Deshboard</a></li>
      <li>
       <a>Portfolio</a></li>
      <li><a>Market</a></li>
      <li><a>Risk Analysis</a></li>
      <li><button className="btn">LogIn/Register</button></li>
    </ul>
  </div>
</div>
   
    );
};
export default Navbar;
