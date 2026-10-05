import {
    BrowserRouter,
    Routes,
    Route,
    Navigate
} from "react-router-dom";

import Register from "./components/Register";
import Login from "./components/Login";
import Products from "./components/Products";
import AddProduct from "./components/AddProduct";
import EditProduct from "./components/EditProduct";

function App() {
    return (
        <BrowserRouter>
            <Routes>

                {/* Home → Register */}
                <Route
                    path="/"
                    element={
                        <Navigate
                            to="/register"
                            replace
                        />
                    }
                />

                {/* Register */}
                <Route
                    path="/register"
                    element={<Register />}
                />

                {/* Login */}
                <Route
                    path="/login"
                    element={<Login />}
                />

                {/* Products */}
                <Route
                    path="/products"
                    element={<Products />}
                />

                {/* Add Product */}
                <Route
                    path="/products/add"
                    element={<AddProduct />}
                />

                {/* Edit Product */}
                <Route
                    path="/products/edit/:id"
                    element={<EditProduct />}
                />

                {/* Invalid URL → Register */}
                <Route
                    path="*"
                    element={
                        <Navigate
                            to="/register"
                            replace
                        />
                    }
                />

            </Routes>
        </BrowserRouter>
    );
}

export default App;