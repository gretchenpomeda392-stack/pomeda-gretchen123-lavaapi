import {
    useEffect,
    useState
} from "react";

import {
    Link,
    useLocation,
    useNavigate
} from "react-router-dom";

import {
    getProducts,
    deleteProduct,
    logout
} from "../api";

function Products() {

    const navigate = useNavigate();
    const location = useLocation();

    const [products, setProducts] =
        useState([]);

    const [loading, setLoading] =
        useState(true);

    const [error, setError] =
        useState("");

    const [message, setMessage] =
        useState("");

    const loadProducts = async () => {

        setLoading(true);
        setError("");

        try {

            const response =
                await getProducts();

            console.log(
                "GET PRODUCTS RESPONSE:",
                response
            );

            /*
             * API response:
             *
             * {
             *   message: "...",
             *   data: [...]
             * }
             */

            if (
                response &&
                Array.isArray(response.data)
            ) {

                setProducts(
                    response.data
                );

            } else {

                setProducts([]);
            }

        } catch (error) {

            console.error(
                "LOAD PRODUCTS ERROR:",
                error
            );

            setError(
                error.message ||
                "Unable to load products."
            );

        } finally {

            setLoading(false);
        }
    };

    useEffect(() => {

        const token =
            localStorage.getItem(
                "access_token"
            );

        if (!token) {

            navigate(
                "/login",
                { replace: true }
            );

            return;
        }

        if (
            location.state?.message
        ) {

            setMessage(
                location.state.message
            );

            /*
             * Remove state so the message
             * does not keep appearing.
             */

            window.history.replaceState(
                {},
                document.title,
                window.location.pathname
            );
        }

        loadProducts();

    }, []);

    const handleDelete = async (
        product
    ) => {

        const confirmed =
            window.confirm(
                `Are you sure you want to delete "${product.product_name}"?`
            );

        if (!confirmed) {
            return;
        }

        setError("");
        setMessage("");

        try {

            const response =
                await deleteProduct(
                    product.id
                );

            setMessage(
                response?.message ||
                "Product deleted successfully."
            );

            await loadProducts();

        } catch (error) {

            console.error(
                "DELETE PRODUCT ERROR:",
                error
            );

            setError(
                error.message ||
                "Unable to delete product."
            );
        }
    };

    const handleLogout = async () => {

        await logout();

        navigate(
            "/login",
            { replace: true }
        );
    };

    return (
        <div className="products-container">

            <div className="product-header">

                <div>
                    <h1>
                        Welcome to Product Lists
                    </h1>

                    <p>
                        Manage your products
                    </p>
                </div>

                <button
                    className="logout-button"
                    onClick={handleLogout}
                >
                    Logout
                </button>

            </div>

            <div className="crud-card">

                <div className="product-list-header">

                    <div>
                        <h2>
                            Product List
                        </h2>

                        <p>
                            View and manage all products
                        </p>
                    </div>

                    <Link
                        to="/products/add"
                        className="add-button"
                    >
                        + Add Product
                    </Link>

                </div>

                {error && (
                    <p className="error">
                        {error}
                    </p>
                )}

                {message && (
                    <p className="success">
                        {message}
                    </p>
                )}

                {loading ? (

                    <div className="empty-state">

                        <p>
                            Loading products...
                        </p>

                    </div>

                ) : products.length === 0 ? (

                    <div className="empty-state">

                        <h3>
                            No Products Yet
                        </h3>

                        <p>
                            Add your first product
                            to get started.
                        </p>

                    </div>

                ) : (

                    <div className="table-container">

                        <table className="product-table">

                            <thead>

                                <tr>

                                    <th>
                                        ID
                                    </th>

                                    <th>
                                        Product Name
                                    </th>

                                    <th>
                                        Description
                                    </th>

                                    <th>
                                        Price
                                    </th>

                                    <th>
                                        Quantity
                                    </th>

                                    <th>
                                        Created At
                                    </th>

                                    <th>
                                        Actions
                                    </th>

                                </tr>

                            </thead>

                            <tbody>

                                {products.map(
                                    (product) => (

                                        <tr
                                            key={
                                                product.id
                                            }
                                        >

                                            <td>
                                                {
                                                    product.id
                                                }
                                            </td>

                                            <td>
                                                <strong>
                                                    {
                                                        product.product_name
                                                    }
                                                </strong>
                                            </td>

                                            <td>
                                                {
                                                    product.description ||
                                                    "-"
                                                }
                                            </td>

                                            <td>
                                                ₱
                                                {Number(
                                                    product.price
                                                ).toLocaleString(
                                                    "en-PH",
                                                    {
                                                        minimumFractionDigits: 2,
                                                        maximumFractionDigits: 2
                                                    }
                                                )}
                                            </td>

                                            <td>
                                                {
                                                    product.quantity
                                                }
                                            </td>

                                            <td>
                                                {
                                                    product.created_at ||
                                                    "-"
                                                }
                                            </td>

                                            <td>

                                                <div className="action-buttons">

                                                    <Link
                                                        to={`/products/edit/${product.id}`}
                                                        className="edit-button"
                                                    >
                                                        Edit
                                                    </Link>

                                                    <button
                                                        className="delete-button"
                                                        onClick={() =>
                                                            handleDelete(
                                                                product
                                                            )
                                                        }
                                                    >
                                                        Delete
                                                    </button>

                                                </div>

                                            </td>

                                        </tr>

                                    )
                                )}

                            </tbody>

                        </table>

                    </div>

                )}

            </div>

        </div>
    );
}

export default Products;