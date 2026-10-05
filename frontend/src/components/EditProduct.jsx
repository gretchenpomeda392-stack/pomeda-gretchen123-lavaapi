import { useEffect, useState } from "react";
import {
    Link,
    useNavigate,
    useParams
} from "react-router-dom";

import {
    getProduct,
    updateProduct
} from "../api";

function EditProduct() {
    const { id } = useParams();
    const navigate = useNavigate();

    const [productName, setProductName] =
        useState("");

    const [description, setDescription] =
        useState("");

    const [price, setPrice] =
        useState("");

    const [quantity, setQuantity] =
        useState("");

    const [loading, setLoading] =
        useState(true);

    const [saving, setSaving] =
        useState(false);

    const [error, setError] =
        useState("");

    useEffect(() => {
        const token =
            localStorage.getItem(
                "access_token"
            );

        if (!token) {
            navigate("/login");
            return;
        }

        loadProduct();
    }, [id]);

    const loadProduct = async () => {
        setLoading(true);
        setError("");

        try {
            const response =
                await getProduct(id);

            const product =
                response.data;

            if (!product) {
                setError(
                    "Product not found."
                );

                return;
            }

            setProductName(
                product.product_name || ""
            );

            setDescription(
                product.description || ""
            );

            setPrice(
                product.price || ""
            );

            setQuantity(
                product.quantity ?? ""
            );

        } catch (error) {
            setError(
                error.message ||
                "Unable to load product."
            );
        } finally {
            setLoading(false);
        }
    };

    const handleSubmit = async (e) => {
        e.preventDefault();

        setError("");
        setSaving(true);

        try {
            await updateProduct(
                id,
                {
                    product_name:
                        productName,

                    description:
                        description,

                    price:
                        price,

                    quantity:
                        quantity
                }
            );

            navigate("/products");

        } catch (error) {
            setError(
                error.message ||
                "Unable to update product."
            );
        } finally {
            setSaving(false);
        }
    };

    if (loading) {
        return (
            <div className="products-container">

                <div className="crud-card">

                    <div className="empty-state">
                        <p>
                            Loading product...
                        </p>
                    </div>

                </div>

            </div>
        );
    }

    return (
        <div className="products-container">

            <div className="product-header">

                <div>
                    <h1>
                        Product Management
                    </h1>

                    <p>
                        Edit product #{id}
                    </p>
                </div>

            </div>

            <div className="crud-card">

                <div className="form-header">

                    <div>
                        <h2>
                            Edit Product
                        </h2>

                        <p>
                            Update the product information below.
                        </p>
                    </div>

                </div>

                {error && (
                    <p className="error">
                        {error}
                    </p>
                )}

                {!error && (
                    <form
                        className="product-form"
                        onSubmit={handleSubmit}
                    >

                        <div className="form-group">

                            <label>
                                Product Name
                            </label>

                            <input
                                type="text"
                                placeholder="Enter product name"
                                value={productName}
                                onChange={(e) =>
                                    setProductName(
                                        e.target.value
                                    )
                                }
                                required
                            />

                        </div>

                        <div className="form-group">

                            <label>
                                Description
                            </label>

                            <textarea
                                placeholder="Enter product description"
                                value={description}
                                onChange={(e) =>
                                    setDescription(
                                        e.target.value
                                    )
                                }
                                rows="4"
                            />

                        </div>

                        <div className="form-row">

                            <div className="form-group">

                                <label>
                                    Price
                                </label>

                                <input
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    placeholder="0.00"
                                    value={price}
                                    onChange={(e) =>
                                        setPrice(
                                            e.target.value
                                        )
                                    }
                                    required
                                />

                            </div>

                            <div className="form-group">

                                <label>
                                    Quantity
                                </label>

                                <input
                                    type="number"
                                    min="0"
                                    placeholder="0"
                                    value={quantity}
                                    onChange={(e) =>
                                        setQuantity(
                                            e.target.value
                                        )
                                    }
                                    required
                                />

                            </div>

                        </div>

                        <div className="form-actions">

                            <Link
                                to="/products"
                                className="back-button"
                            >
                                Back
                            </Link>

                            <button
                                type="submit"
                                className="edit-button"
                                disabled={saving}
                            >
                                {saving
                                    ? "Updating..."
                                    : "Update Product"}
                            </button>

                        </div>

                    </form>
                )}

            </div>

        </div>
    );
}

export default EditProduct;