<?php namespace VahidIrn\NeoEloquent\Eloquent\Edges;

use VahidIrn\NeoEloquent\Connection;
use VahidIrn\NeoEloquent\Eloquent\Model;
use VahidIrn\NeoEloquent\QueryException;
use VahidIrn\NeoEloquent\Eloquent\Builder;
use VahidIrn\NeoEloquent\UnknownDirectionException;

abstract class Delegate {

     /**
     * The Eloquent builder instance.
     *
     * @var \VahidIrn\NeoEloquent\Eloquent\Builder
     */
    protected $query;

    /**
     * The database connection.
     *
     * @var \VahidIrn\NeoEloquent\Connection
     */
    protected $connection;

    /**
     * The database client.
     *
     * @var \Everyman\Neo4j\Client
     */
    protected $client;

    /**
     * Create a new delegate instance.
     *
     * @param \VahidIrn\NeoEloquent\Eloquent\Builder $query
     * @param \VahidIrn\NeoEloquent\Eloquent\Model   $parent
     */
    public function __construct(Builder $query)
    {
        $this->query  = $query;
        $model = $query->getModel();

        // Setup the database connection and client.
        $this->connection = $model->getConnection();
        $this->client = $this->connection->getClient();
    }

    /**
     * Get a new Finder instance.
     *
     * @return \VahidIrn\NeoEloquent\Eloquent\Edges\Finder
     */
    public function newFinder()
    {
        return new Finder($this->query);
    }

    /**
     * Make a new Relationship instance.
     *
     * @param  string $type
     * @param  \VahidIrn\NeoEloquent\Eloquent\Model $startModel
     * @param  \VahidIrn\NeoEloquent\Eloquent\Model $endModel
     * @param  array  $properties
     * @return \Everyman\Neo4j\Relationship
     */
    protected function makeRelationship($type, $startModel, $endModel, $properties = array())
    {
        return $this->client
            ->makeRelationship()
            ->setType($this->type)
            ->setStartNode($this->start)
            ->setEndNode($this->end)
            ->setProperties($this->attributes);
    }

    /**
     * Start a batch operation with the database.
     *
     * @return \Everyman\Neo4j\Batch
     */
    public function prepareBatch()
    {
        return $this->client->startBatch();
    }

    /**
     * Commit the started batch operation.
     *
     * @return boolean
     *
     * @throws  \VahidIrn\NeoEloquent\QueryException If no open batch to commit.
     */
    public function commitBatch()
    {
        try {

            return $this->client->commitBatch();

        } catch (\Exception $e)
        {
            throw new QueryException('Error committing batch operation.', array(), $e);
        }
    }

    /**
     * Get the direction value from the Neo4j
     * client according to the direction set on
     * the inheriting class,
     *
     * @param  string $direction
     * @return string
     *
     * @throws UnknownDirectionException If the specified $direction is not one of in, out or inout
     */
    public function getRealDirection($direction)
    {
        if ($direction == 'in' || $direction == 'out')
        {
            $direction = ucfirst($direction);

        } elseif ($direction == 'any')
        {
            $direction = 'All';

        } else
        {
            throw new UnknownDirectionException($direction);
        }

        $direction = "Direction". $direction;

        return constant("Everyman\Neo4j\Relationship::". $direction);
    }

    /**
     * Convert a model to a Node object.
     *
     * @param  \VahidIrn\NeoEloquent\Eloquent\Model $model
     * @return \Everyman\Neo4j\Node
     */
    public function asNode(Model $model)
    {
        $node = $this->client->makeNode();

        // If the key name of the model is 'id' we will need to set it properly with setId()
        // since setting it as a regular property with setProperty() won't cut it.
        if ($model->getKeyName() == 'id')
        {
            $node->setId($model->getKey());
        }

        // In this case the dev has chosen a different primary key
        // so we use it insetead.
        else
        {
            $node->setProperty($model->getKeyName(), $model->getKey());
        }

        return $node;
    }

    /**
     * Get the NeoEloquent connection for this relation.
     *
     * @return \VahidIrn\NeoEloquent\Connection
     */
    public function getConnection()
    {
        return $this->connection;
    }

    /**
     * Set the database connection.
     *
     * @param  \VahidIrn\NeoEloquent\Connection  $name
     * @return void
     */
    public function setConnection(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * Get the current connection name.
     *
     * @return string
     */
    public function getConnectionName()
    {
        return $this->query->getModel()->getConnectionName();
    }

}
