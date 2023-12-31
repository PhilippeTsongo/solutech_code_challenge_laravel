<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\User;
use App\Models\Subcategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class BookController extends Controller
{
    //access level control: only admin can access to store, edit, update, destroy, availableStatus and unavailableStatus
    public function __construct()
    {
        $this->middleware(['IsAdmin'])->only('edit', 'store', 'update', 'destroy', 'availableStatus', 'unavailableStatus');
    }

    public function index()
    {
        try{
            $books = Book::orderBy('created_at', 'DESC')->get();

            $available_books = Book::where('status', 'AVAILABLE')->get();
            $unavailable_books = Book::where('status', 'UNAVAILABLE')->get();

            foreach ($books as $book_data) {
                $book_category = $book_data->category;
                $book_sub_category = $book_data->subcategory;
            }

            $available = $available_books->count();
            $unavailable = $unavailable_books->count();
            $total = $books->count();

            $data = array(
                'message' => "success",
                'books' => $books,
                'available' => $available,
                'unavailable' => $unavailable,
                'total' => $total,
                'status' => 200
            );
                
            return response()->json($data, 200);
            
            }catch(\Exception $e){
                    return response()->json(['message' => 'Failed to fetch books ' .$e->getMessage(), 'status' => 500]);
        }
    }
    
    public function store(Request $request)
    {
        
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string'],
            'publisher' => ['required', 'string'],
            'isbn' => ['required', 'unique:books'],
            'subcategory' => ['required'],
            'page' => ['required', 'integer'],
            'image' => ['required', 'image', 'mimes:jpeg,png,jpg,gif,svg', 'max:5048'], // Allow various image formats with a max size of 5MB
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {   

            $random_id = random_int(1000000, 9000000) + 1; // Generates a cryptographically secure random number
            //book's image
            if ($request->hasFile('image')) {
                $file = $request->file('image');
                $fileName = $random_id;

                //path in the storage/app/public directory
                $path = $file->storeAs('IMAGES/BOOKS', $fileName, 'public');

            } else {
                $path = null; // If no file is uploaded, set path to null
            }

            $category = Subcategory::where('id', $request->subcategory)->first();

            // Create user
            $book = new Book($request->all());

            $book->id = $random_id;
            $book->added_by = Auth::id();
            $book->image = $path;
            $book->category_id = $category->category_id;
            $book->subcategory_id = $request->subcategory;
            $book->image = $path;


            $book->save();
           
            return response()->json(['message' => 'Book created successfully', 'book' => $book], 201);
            
        } catch (\Exception $e) {
            // Something went wrong
            return response()->json(['error' => 'Failed to create a book ' . $e->getMessage()], 500);
        }
    }

   
    public function show(Book $book)
    {
        try{

            $book_category = $book->category;
            $book_sub_category = $book->subcategory;

            $data = array(
                'message' => 'success',
                'book' => $book,
                'status' => 200
            );
            return response()->json($data, 200);

        } catch (\Exception $e) {
            // Something went wrong
            return response()->json(['error' => 'Failed to fetch a unique book. ' . $e->getMessage()], 500);
        }
    }

   
    public function edit($id)
    {
        //
    }

    
    public function update(Request $request, Book $book)
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string'],
            'publisher' => ['required', 'string'],
            'subcategory' => ['required'],
            'page' => ['required', 'integer'],
            // 'image' => ['nullable', 'mimes:jpeg,png,jpg,gif,svg', 'max:5048'], // Allow various image formats with a max size of 5MB

        ]);

        //return the validation error if there is any
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {   

            $random_id = random_int(100000, 900000) + 1; // Generates a cryptographically secure random number
    
            $book->fill($request->all());
        
            //book's image
            if ($request->hasFile('image')) {

                // Delete the existing image file
                if ($book->image) {
                    Storage::disk('public')->delete($book->image);
                }
        
                $file = $request->file('image');
                $fileName = $random_id;

                //path in the storage/app/public directory

                $path = $file->storeAs('IMAGES/BOOKS', $fileName, 'public');

            } else {
                $path = null; // If no file is uploaded, set path to null
            }
                
            $category = Subcategory::where('id', $request->subcategory)->first();

            $book->id = $random_id;
            $book->category_id = $category->category_id;
            $book->subcategory_id = $request->subcategory;
            // $book->image = $path;
            $book->added_by = Auth::id();            
        
            $book->save();
        
            return response()->json(['message' => 'Book updated successfully', 'book' => $book], 201);

        } catch (\Exception $e) {
            // Something went wrong
            return response()->json(['error' => 'Failed to update the book. ' . $e->getMessage()], 500);
        }
    }

    
    public function destroy(Book $book)
    {
        try {
            // Delete the associated image file from the storage if it exists
            if ($book->image) {

                if (File::exists($book->image)) {
                    // Delete the file
                    File::delete($book->image);
                }
            }

            //Prevent to delete a borrwed book
            if($book->loans){
                foreach ($book->loans as $book_loan) {
                    if($book_loan->extend == 1 OR $book_loan->status == 'APPROVED' ){
                        return response()->json(['message' => 'This book can\'t be deleted because it has been borrowed !', 'status' => 400], 400);
                    }
                }
            }

            //prevent to delete an available book
            if($book->status == 'AVAILABLE' ){
                return response()->json(['message' => 'This book can\'t be deleted because it\'s available!', 'status' => 411], 411);
            }


            // Soft delete the book will not be deleted instead the field deleted_at will take the value
            $book->delete();
    
            return response()->json(['message' => 'Book deleted successfully!', 'status' => 200], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error', 'Book not found! ' . $e->getMessage(), 'status' => 400], 400);
        } catch (\Exception $e) {
            return response()->json(['error', 'An error occurred while deleting the book. ' . $e->getMessage(), 'status' => 500], 500);
        }
    }

    
    public function availableStatus(Book $book)
    {
        try{
            $book->update([
                'status' => 'AVAILABLE'
            ]);
            return response()->json(['message' => 'Book\'s status changed to available successfully',  'status' => 200], 200);
        }
        catch (\Exception $e){
            return response()->json(['message' => 'Error occured while updating ' . $e->getMessage(), 'status' => 500]);
        }
    }

    public function unavailableStatus(Book $book)
    {
        try{
            $book->update([
                'status' => 'UNAVAILABLE'
            ]);
            return response()->json(['message' => 'Book\'s status changed to unavailable successfully',  'status' => 200], 200);
        }
        catch (\Exception $e){
            return response()->json(['message' => 'Error occured while updating ' . $e->getMessage(), 'status' => 500]);
        }
    }
}
